<?php

namespace Tests\Feature\Api;

use App\Actions\Integrations\GenerateApiKeyAction;
use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IntakeApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->createRole('Admin');
        $this->createRole('Client');

        $this->admin = User::factory()->for($this->tenant)->create();
        $this->admin->assignRole('Admin');

        $settings = $this->tenant->settings ?? [];
        $settings['api_enabled'] = true;
        $settings['currency'] = 'USD';
        $this->tenant->update(['settings' => $settings]);

        $generator = $this->app->make(GenerateApiKeyAction::class);
        $this->apiKey = $generator->execute($this->tenant);
    }

    public function test_it_creates_order_and_client_via_api(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/intake', [
            'client' => [
                'name' => 'Jane Client',
                'email' => 'jane@example.com',
                'phone' => '123456789',
                'company' => 'Client Co',
            ],
            'order' => [
                'title' => 'Sample Order',
                'instructions' => 'Please digitize this.',
                'priority' => 'rush',
                'type' => 'vector',
                'price_amount' => 125.50,
                'currency' => 'usd',
            ],
        ], [
            'Authorization' => 'Bearer '.$this->apiKey,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'order_id',
                'order_number',
                'client_id',
                'client_user_created',
            ]);

        $this->assertDatabaseHas('clients', [
            'tenant_id' => $this->tenant->id,
            'email' => 'jane@example.com',
        ]);

        $this->assertDatabaseHas('orders', [
            'tenant_id' => $this->tenant->id,
            'title' => 'Sample Order',
            'status' => OrderStatus::RECEIVED->value,
            'priority' => 'rush',
            'type' => 'vector',
            'price' => 125.50,
        ]);

        $clientUser = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($clientUser);
        Notification::assertSentTo($clientUser, ResetPassword::class);
    }

    public function test_missing_api_key_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/v1/intake', []);

        $response->assertStatus(401);
    }

    public function test_validation_errors_return_422(): void
    {
        $response = $this->postJson('/api/v1/intake', [
            'client' => [
                'name' => 'Jane',
                'email' => 'invalid-email',
            ],
            'order' => [
                'title' => '',
            ],
        ], [
            'Authorization' => 'Bearer '.$this->apiKey,
        ]);

        $response->assertStatus(422);
    }

    protected function createRole(string $name): void
    {
        Role::firstOrCreate(
            [
                'tenant_id' => $this->tenant->id,
                'name' => $name,
            ],
            [
                'guard_name' => 'web',
            ]
        );
    }
}

<?php

namespace Tests\Feature\Api;

use App\Actions\Integrations\GenerateApiKeyAction;
use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    public function test_it_stores_uploaded_files_with_the_order(): void
    {
        Notification::fake();
        Storage::fake('local');
        $this->setUploadSettings('jpg,png,pdf', 5);

        $response = $this->post('/api/v1/intake', $this->quotePayload([
            'files' => [
                UploadedFile::fake()->image('logo.png'),
                UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf'),
            ],
        ]), $this->apiHeaders());

        $response->assertCreated();

        $this->assertDatabaseCount('order_files', 2);
        $this->assertDatabaseHas('order_files', [
            'order_id' => $response->json('order_id'),
            'tenant_id' => $this->tenant->id,
            'type' => 'input',
            'original_name' => 'logo.png',
            'uploaded_by_user_id' => $this->admin->id,
        ]);
    }

    public function test_it_rejects_extensions_not_allowed_in_settings(): void
    {
        Notification::fake();
        Storage::fake('local');
        $this->setUploadSettings('jpg,png', 5);

        $response = $this->post('/api/v1/intake', $this->quotePayload([
            'files' => [UploadedFile::fake()->create('virus.exe', 10)],
        ]), $this->apiHeaders());

        $response->assertStatus(422)->assertJsonValidationErrors('files.0');
        $this->assertDatabaseCount('order_files', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_rejects_files_larger_than_the_configured_limit(): void
    {
        Notification::fake();
        Storage::fake('local');
        $this->setUploadSettings('jpg,png', 1);

        $response = $this->post('/api/v1/intake', $this->quotePayload([
            'files' => [UploadedFile::fake()->create('big.jpg', 2048)],
        ]), $this->apiHeaders());

        $response->assertStatus(422)->assertJsonValidationErrors('files.0');
        $this->assertDatabaseCount('orders', 0);
    }

    protected function setUploadSettings(string $extensions, int $maxMb): void
    {
        $settings = $this->tenant->fresh()->settings ?? [];
        $settings['allowed_input_extensions'] = $extensions;
        $settings['max_upload_mb'] = $maxMb;
        $this->tenant->update(['settings' => $settings]);
    }

    protected function apiHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->apiKey, 'Accept' => 'application/json'];
    }

    protected function quotePayload(array $extra = []): array
    {
        return array_merge([
            'client' => ['name' => 'Jane Client', 'email' => 'jane@example.com'],
            'order' => ['title' => 'Quote with files', 'is_quote' => 1],
        ], $extra);
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

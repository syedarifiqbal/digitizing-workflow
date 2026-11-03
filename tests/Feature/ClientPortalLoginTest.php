<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ClientPortalCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClientPortalLoginTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Client $client;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->client = Client::factory()->for($this->tenant)->create(['email' => 'client@example.com']);

        Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Client']);
        $adminRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin']);

        $this->admin = User::factory()->for($this->tenant)->create();
        $this->admin->roles()->attach($adminRole);
    }

    public function test_admin_can_create_a_ready_to_use_login_and_email_it(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'client@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user = User::where('client_id', $this->client->id)->firstOrFail();
        $this->assertTrue(Hash::check('Sup3rSecret!', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('Client'));

        Notification::assertSentTo($user, ClientPortalCredentialsNotification::class);
    }

    public function test_client_can_sign_in_with_the_login_without_signing_up(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->put(route('clients.portal-login.update', $this->client), [
            'email' => 'client@example.com',
            'password' => 'Sup3rSecret!',
            'send_email' => false,
        ]);

        auth()->logout();

        $this->post(route('login'), ['email' => 'client@example.com', 'password' => 'Sup3rSecret!'])
            ->assertRedirect(route('client.dashboard'));

        $this->get(route('client.dashboard'))->assertOk();
    }

    public function test_login_can_be_saved_without_emailing(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->put(route('clients.portal-login.update', $this->client), [
            'email' => 'client@example.com',
            'password' => 'Sup3rSecret!',
            'send_email' => false,
        ])->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_existing_login_can_be_changed_and_keeps_password_when_left_empty(): void
    {
        Notification::fake();
        $existing = User::factory()->for($this->tenant)->create([
            'client_id' => $this->client->id,
            'email' => 'old@example.com',
            'password' => 'OldPassw0rd!',
            'email_verified_at' => null,
        ]);

        $this->actingAs($this->admin)->put(route('clients.portal-login.update', $this->client), [
            'email' => 'new@example.com',
            'password' => '',
            'send_email' => false,
        ])->assertSessionDoesntHaveErrors();

        $existing->refresh();
        $this->assertSame('new@example.com', $existing->email);
        $this->assertTrue(Hash::check('OldPassw0rd!', $existing->password));
        $this->assertNotNull($existing->email_verified_at);
        $this->assertSame(1, User::where('client_id', $this->client->id)->count());
    }

    public function test_password_is_required_for_a_new_login_and_when_emailing(): void
    {
        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'client@example.com',
                'password' => '',
                'send_email' => false,
            ])
            ->assertSessionHasErrors('password');

        $existing = User::factory()->for($this->tenant)->create(['client_id' => $this->client->id]);

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => $existing->email,
                'password' => '',
                'send_email' => true,
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_email_must_not_belong_to_another_user_in_the_tenant(): void
    {
        $other = User::factory()->for($this->tenant)->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => $other->email,
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['client_id' => $this->client->id]);
    }

    public function test_non_admin_cannot_manage_client_logins(): void
    {
        $designer = User::factory()->for($this->tenant)->create();

        $this->actingAs($designer)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'client@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertForbidden();
    }

    public function test_client_in_another_tenant_cannot_be_managed(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherClient = Client::factory()->for($otherTenant)->create();

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $otherClient), [
                'email' => 'x@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertNotFound();
    }

    public function test_show_page_exposes_portal_login_state(): void
    {
        $this->actingAs($this->admin)
            ->get(route('clients.show', $this->client))
            ->assertInertia(fn ($page) => $page
                ->where('portalLogin.exists', false)
            );
    }
}

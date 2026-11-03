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

    public function test_email_of_a_team_member_cannot_be_used(): void
    {
        $designerRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Designer']);
        $designer = User::factory()->for($this->tenant)->create(['email' => 'team@example.com']);
        $designer->roles()->attach($designerRole);

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'team@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['client_id' => $this->client->id]);
    }

    public function test_email_of_another_clients_login_cannot_be_used(): void
    {
        $otherClient = Client::factory()->for($this->tenant)->create();
        $clientRole = Role::where('tenant_id', $this->tenant->id)->where('name', 'Client')->first();
        $other = User::factory()->for($this->tenant)->create(['client_id' => $otherClient->id, 'email' => 'other@example.com']);
        $other->roles()->attach($clientRole);

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'other@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_login_of_a_deleted_client_with_the_same_email_is_reused(): void
    {
        Notification::fake();
        $clientRole = Role::where('tenant_id', $this->tenant->id)->where('name', 'Client')->first();
        $deletedClient = Client::factory()->for($this->tenant)->create();
        $old = User::factory()->for($this->tenant)->create(['client_id' => $deletedClient->id, 'email' => 'client@example.com']);
        $old->roles()->attach($clientRole);
        $deletedClient->delete();

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'client@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame($this->client->id, $old->fresh()->client_id);
    }

    public function test_conflict_message_says_who_owns_the_email(): void
    {
        $designerRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Designer']);
        $designer = User::factory()->for($this->tenant)->create(['email' => 'team@example.com']);
        $designer->roles()->attach($designerRole);

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'team@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertSessionHasErrors(['email' => 'This email already belongs to a team member (Designer). Use a different email for this client.']);
    }

    public function test_unlinked_old_client_login_with_the_same_email_is_reused(): void
    {
        Notification::fake();
        $clientRole = Role::where('tenant_id', $this->tenant->id)->where('name', 'Client')->first();
        $old = User::factory()->for($this->tenant)->create(['client_id' => null, 'email' => 'client@example.com']);
        $old->roles()->attach($clientRole);

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'client@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => true,
            ])
            ->assertSessionDoesntHaveErrors();

        $old->refresh();
        $this->assertSame($this->client->id, $old->client_id);
        $this->assertTrue(Hash::check('Sup3rSecret!', $old->password));
        $this->assertSame(1, User::where('email', 'client@example.com')->count());
        Notification::assertSentTo($old, ClientPortalCredentialsNotification::class);
    }

    public function test_soft_deleted_client_login_with_the_same_email_is_restored(): void
    {
        Notification::fake();
        $clientRole = Role::where('tenant_id', $this->tenant->id)->where('name', 'Client')->first();
        $old = User::factory()->for($this->tenant)->create(['client_id' => $this->client->id, 'email' => 'client@example.com']);
        $old->roles()->attach($clientRole);
        $old->delete();

        $this->actingAs($this->admin)
            ->put(route('clients.portal-login.update', $this->client), [
                'email' => 'client@example.com',
                'password' => 'Sup3rSecret!',
                'send_email' => false,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertNull($old->fresh()->deleted_at);
        $this->assertSame(1, User::withTrashed()->where('email', 'client@example.com')->count());
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

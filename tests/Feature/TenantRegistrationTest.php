<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_signup_creates_tenant_and_admin_user(): void
    {
        $response = $this->post('/register', [
            'company' => 'Acme Digitizing',
            'name' => 'Jane Owner',
            'email' => 'owner@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertRedirect(route('dashboard'));

        $tenant = Tenant::where('name', 'Acme Digitizing')->first();
        $this->assertNotNull($tenant);

        $user = User::where('email', 'owner@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($tenant->id, $user->tenant_id);
        $this->assertTrue($user->hasRole('Admin'));
    }
}


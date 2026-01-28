<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_designer_cannot_view_unassigned_order(): void
    {
        $tenant = Tenant::factory()->create();
        $designerRole = Role::create(['tenant_id' => $tenant->id, 'name' => 'Designer']);

        $assignedDesigner = User::factory()->for($tenant)->create();
        $assignedDesigner->roles()->attach($designerRole);

        $otherDesigner = User::factory()->for($tenant)->create();
        $otherDesigner->roles()->attach($designerRole);

        $order = Order::factory()
            ->for($tenant)
            ->for(Client::factory()->for($tenant), 'client')
            ->create(['designer_id' => $assignedDesigner->id]);

        $this->actingAs($otherDesigner)
            ->get(route('orders.show', $order))
            ->assertStatus(403);
    }

    public function test_client_can_view_own_order(): void
    {
        $tenant = Tenant::factory()->create();
        $clientRole = Role::create(['tenant_id' => $tenant->id, 'name' => 'Client']);

        $client = Client::factory()->for($tenant)->create();

        $clientUser = User::factory()->for($tenant)->create([
            'client_id' => $client->id,
        ]);
        $clientUser->roles()->attach($clientRole);

        $order = Order::factory()
            ->for($tenant)
            ->for($client, 'client')
            ->create();

        $this->actingAs($clientUser)
            ->get(route('orders.show', $order))
            ->assertStatus(200);
    }

    public function test_client_cannot_access_staff_order_list(): void
    {
        $tenant = Tenant::factory()->create();
        $clientRole = Role::create(['tenant_id' => $tenant->id, 'name' => 'Client']);

        $client = Client::factory()->for($tenant)->create();
        $clientUser = User::factory()->for($tenant)->create([
            'client_id' => $client->id,
        ]);
        $clientUser->roles()->attach($clientRole);

        $this->actingAs($clientUser)
            ->get(route('orders.index'))
            ->assertStatus(403);
    }
}


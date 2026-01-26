<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Client $client;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->client = Client::factory()->for($this->tenant)->create();

        $clientRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Client']);

        $this->clientUser = User::factory()->for($this->tenant)->create([
            'client_id' => $this->client->id,
        ]);
        $this->clientUser->roles()->attach($clientRole);
    }

    public function test_client_can_view_dashboard(): void
    {
        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->count(5)
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Dashboard')
            ->has('recentOrders')
            ->has('stats')
        );
    }

    public function test_dashboard_shows_orders_needing_attention(): void
    {
        // Create delivered order (needs attention)
        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->delivered()
            ->create();

        // Create revision requested order (needs attention)
        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create(['status' => OrderStatus::REVISION_REQUESTED]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('ordersNeedingAttention', 2)
        );
    }

    public function test_client_can_view_orders_list(): void
    {
        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->count(10)
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Orders/Index')
            ->has('orders.data')
        );
    }

    public function test_client_can_filter_orders_by_status(): void
    {
        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create(['status' => OrderStatus::RECEIVED]);

        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->delivered()
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.index', ['status' => 'delivered']));

        $response->assertStatus(200);
    }

    public function test_client_can_filter_orders_by_priority(): void
    {
        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->rush()
            ->create();

        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.index', ['priority' => 'rush']));

        $response->assertStatus(200);
    }

    public function test_client_can_search_orders(): void
    {
        Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->create(['order_number' => 'ORD-12345', 'title' => 'Test Order']);

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.index', ['search' => 'ORD-12345']));

        $response->assertStatus(200);
    }

    public function test_client_cannot_view_other_clients_orders(): void
    {
        $otherClient = Client::factory()->for($this->tenant)->create();

        $otherOrder = Order::factory()
            ->for($this->tenant)
            ->for($otherClient, 'client')
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.show', $otherOrder));

        $response->assertStatus(403);
    }

    public function test_client_can_view_create_order_form(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Orders/Create')
            ->has('client')
            ->has('allowedInputExtensions')
            ->has('maxUploadMb')
        );
    }

    public function test_user_without_client_record_cannot_access_portal(): void
    {
        $staffUser = User::factory()->for($this->tenant)->create([
            'client_id' => null,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('client.dashboard'));

        $response->assertStatus(403);
    }

    public function test_client_can_view_order_details(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->delivered()
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.show', $order));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Orders/Show')
            ->has('order')
            ->has('inputFiles')
        );
    }

    public function test_client_sees_output_files_only_after_submission(): void
    {
        // Order in progress - should not show output files
        $inProgressOrder = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->inProgress()
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.show', $inProgressOrder));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('showOutputFiles', false)
        );

        // Submitted order - should show output files
        $submittedOrder = Order::factory()
            ->for($this->tenant)
            ->for($this->client, 'client')
            ->submitted()
            ->create();

        $response = $this->actingAs($this->clientUser)
            ->get(route('client.orders.show', $submittedOrder));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('showOutputFiles', true)
        );
    }
}

<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionCalculationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_commissions_are_created_on_delivery(): void
    {
        $tenant = Tenant::factory()->create([
            'settings' => [
                'sales_commission_earned_on' => 'delivered',
                'designer_bonus_earned_on' => 'delivered',
            ],
        ]);

        $client = Client::factory()->for($tenant)->create();

        $adminRole = Role::create(['tenant_id' => $tenant->id, 'name' => 'Admin']);
        $designerRole = Role::create(['tenant_id' => $tenant->id, 'name' => 'Designer']);
        $salesRole = Role::create(['tenant_id' => $tenant->id, 'name' => 'Sales']);

        $admin = User::factory()->for($tenant)->create();
        $admin->roles()->attach($adminRole);

        $designer = User::factory()->for($tenant)->create();
        $designer->roles()->attach($designerRole);

        $sales = User::factory()->for($tenant)->create();
        $sales->roles()->attach($salesRole);

        CommissionRule::factory()
            ->for($tenant)
            ->for($sales)
            ->forSales()
            ->percent(10.0)
            ->create(['currency' => 'USD']);

        CommissionRule::factory()
            ->for($tenant)
            ->for($designer)
            ->forDesigner()
            ->percent(5.0)
            ->create(['currency' => 'USD']);

        $order = Order::factory()
            ->for($tenant)
            ->for($client, 'client')
            ->create([
                'status' => OrderStatus::APPROVED,
                'price' => 200.00,
                'designer_id' => $designer->id,
                'sales_user_id' => $sales->id,
                'created_by' => $admin->id,
            ]);

        $response = $this->actingAs($admin)
            ->post(route('orders.deliver', $order), [
                'message' => 'Delivered',
                'designer_tip' => 15,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('commissions', [
            'order_id' => $order->id,
            'user_id' => $sales->id,
            'role_type' => 'sales',
            'base_amount' => '20.00', // 10% of 200
        ]);

        $this->assertDatabaseHas('commissions', [
            'order_id' => $order->id,
            'user_id' => $designer->id,
            'role_type' => 'designer',
            'base_amount' => '10.00', // 5% of 200
            'extra_amount' => '15.00',
            'total_amount' => '25.00',
        ]);
    }
}

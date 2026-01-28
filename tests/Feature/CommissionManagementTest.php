<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $designer;
    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        // Create roles
        $adminRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin']);
        $designerRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Designer']);
        $salesRole = Role::create(['tenant_id' => $this->tenant->id, 'name' => 'Sales']);

        // Create users
        $this->admin = User::factory()->for($this->tenant)->create();
        $this->admin->roles()->attach($adminRole);

        $this->designer = User::factory()->for($this->tenant)->create();
        $this->designer->roles()->attach($designerRole);

        $this->sales = User::factory()->for($this->tenant)->create();
        $this->sales->roles()->attach($salesRole);
    }

    public function test_admin_can_view_all_commissions(): void
    {
        $commission = Commission::factory()
            ->for($this->tenant)
            ->for($this->sales)
            ->forSales()
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('commissions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Commissions/Index')
            ->has('commissions.data')
        );
    }

    public function test_admin_can_filter_commissions_by_role_type(): void
    {
        Commission::factory()
            ->for($this->tenant)
            ->for($this->sales)
            ->forSales()
            ->create();

        Commission::factory()
            ->for($this->tenant)
            ->for($this->designer)
            ->forDesigner()
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('commissions.index', ['role_type' => 'sales']));

        $response->assertStatus(200);
    }

    public function test_admin_can_filter_commissions_by_user(): void
    {
        Commission::factory()
            ->for($this->tenant)
            ->for($this->sales)
            ->create();

        Commission::factory()
            ->for($this->tenant)
            ->for($this->designer)
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('commissions.index', [
                'role_type' => 'sales',
                'user_id' => $this->sales->id,
            ]));

        $response->assertStatus(200);
    }

    public function test_sales_user_can_view_their_earnings(): void
    {
        Commission::factory()
            ->for($this->tenant)
            ->for($this->sales)
            ->forSales()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->sales)
            ->get(route('commissions.my'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Commissions/MyEarnings')
            ->has('commissions.data')
            ->has('totals')
        );
    }

    public function test_designer_can_view_their_earnings(): void
    {
        Commission::factory()
            ->for($this->tenant)
            ->for($this->designer)
            ->forDesigner()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->designer)
            ->get(route('commissions.my'));

        $response->assertStatus(200);
    }

    public function test_admin_can_update_commission_tip(): void
    {
        $commission = Commission::factory()
            ->for($this->tenant)
            ->for($this->designer)
            ->forDesigner()
            ->create([
                'base_amount' => 10.00,
                'extra_amount' => 0,
                'total_amount' => 10.00,
            ]);

        $response = $this->actingAs($this->admin)
            ->post(route('commissions.update-tip', $commission), [
                'extra_amount' => 15.00,
                'notes' => 'Excellent work',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $commission->refresh();
        $this->assertEquals(15.00, $commission->extra_amount);
        $this->assertEquals(25.00, $commission->total_amount);
    }

    public function test_non_admin_cannot_update_commission_tip(): void
    {
        $commission = Commission::factory()
            ->for($this->tenant)
            ->for($this->designer)
            ->forDesigner()
            ->create();

        $response = $this->actingAs($this->sales)
            ->post(route('commissions.update-tip', $commission), [
                'extra_amount' => 15.00,
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_commission_rules_index(): void
    {
        CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->sales)
            ->forSales()
            ->create();

        $response = $this->actingAs($this->admin)
            ->get(route('commission-rules.sales.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CommissionRules/Index')
            ->has('rules')
        );
    }

    public function test_admin_can_create_commission_rule(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('commission-rules.store'), [
                'user_id' => $this->sales->id,
                'role_type' => 'sales',
                'type' => 'percent',
                'percent_rate' => 10.00,
                'currency' => 'USD',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('commission_rules', [
            'user_id' => $this->sales->id,
            'role_type' => 'sales',
            'percent_rate' => 10.00,
        ]);
    }

    public function test_admin_can_update_commission_rule(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->sales)
            ->forSales()
            ->percent(10.00)
            ->create();

        $response = $this->actingAs($this->admin)
            ->put(route('commission-rules.update', ['commissionRule' => $rule]), [
                'user_id' => $this->sales->id,
                'type' => 'percent',
                'percent_rate' => 12.00,
                'currency' => 'USD',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rule->refresh();
        $this->assertEquals(12.00, $rule->percent_rate);
    }

    public function test_admin_can_deactivate_commission_rule(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->sales)
            ->forSales()
            ->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('commission-rules.destroy', ['commissionRule' => $rule]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('commission_rules', [
            'id' => $rule->id,
        ]);
    }
}

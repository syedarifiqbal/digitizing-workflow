<?php

namespace Tests\Unit;

use App\Enums\CommissionType;
use App\Enums\RoleType;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CommissionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected CommissionCalculator $calculator;
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(CommissionCalculator::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
    }

    public function test_calculates_fixed_commission_correctly(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->fixed(25.00)
            ->forSales()
            ->create();

        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create();
        $order->update(['price' => 100.00]);

        $commission = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $this->assertNotNull($commission);
        $this->assertEquals(25.00, $commission->base_amount);
        $this->assertEquals(25.00, $commission->total_amount);
    }

    public function test_calculates_percent_commission_correctly(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->percent(10.00)
            ->forSales()
            ->create();

        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create();
        $order->update(['price' => 100.00]);

        $commission = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $this->assertNotNull($commission);
        $this->assertEquals(10.00, $commission->base_amount);
        $this->assertEquals(10.00, $commission->total_amount);
    }

    public function test_calculates_hybrid_commission_correctly(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->hybrid(5.00, 5.00) // $5 fixed + 5%
            ->forSales()
            ->create();

        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create();
        $order->update(['price' => 100.00]);

        $commission = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $this->assertNotNull($commission);
        $this->assertEquals(10.00, $commission->base_amount); // $5 + ($100 * 0.05)
        $this->assertEquals(10.00, $commission->total_amount);
    }

    public function test_includes_extra_amount_in_total(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->percent(10.00)
            ->forDesigner()
            ->create();

        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create();
        $order->update(['price' => 100.00]);

        $commission = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::DESIGNER,
            'delivered',
            15.00 // tip
        );

        $this->assertNotNull($commission);
        $this->assertEquals(10.00, $commission->base_amount);
        $this->assertEquals(15.00, $commission->extra_amount);
        $this->assertEquals(25.00, $commission->total_amount);
        $this->assertStringContainsString('tip', $commission->notes);
    }

    public function test_prevents_duplicate_commissions(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->percent(10.00)
            ->forSales()
            ->create();

        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create();
        $order->update(['price' => 100.00]);

        $commission1 = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $commission2 = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $this->assertEquals($commission1->id, $commission2->id);
        $this->assertEquals(1, Commission::count());
    }

    public function test_returns_null_when_no_active_rule_exists(): void
    {
        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create();
        $order->update(['price' => 100.00]);

        $commission = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $this->assertNull($commission);
    }

    public function test_returns_null_for_percent_commission_without_price(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->percent(10.00)
            ->forSales()
            ->create();

        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create(['price' => null]);

        $commission = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $this->assertNull($commission);
    }

    public function test_updates_extra_amount_correctly(): void
    {
        $commission = Commission::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->forDesigner()
            ->create([
                'base_amount' => 10.00,
                'extra_amount' => 0,
                'total_amount' => 10.00,
            ]);

        $updated = $this->calculator->updateExtraAmount(
            $commission,
            15.00,
            'Excellent work bonus'
        );

        $this->assertEquals(10.00, $updated->base_amount);
        $this->assertEquals(15.00, $updated->extra_amount);
        $this->assertEquals(25.00, $updated->total_amount);
        $this->assertStringContainsString('Excellent work bonus', $updated->notes);
    }

    public function test_stores_rule_snapshot(): void
    {
        $rule = CommissionRule::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->percent(10.00)
            ->forSales()
            ->create();

        $order = Order::factory()
            ->for($this->tenant)
            ->delivered()
            ->create();
        $order->update(['price' => 100.00]);

        $commission = $this->calculator->calculateAndCreate(
            $order,
            $this->user->id,
            RoleType::SALES,
            'delivered'
        );

        $this->assertIsArray($commission->rule_snapshot);
        $this->assertEquals('percent', $commission->rule_snapshot['type']);
        $this->assertEquals(10.00, $commission->rule_snapshot['percent_rate']);
    }
}

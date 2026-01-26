<?php

namespace Database\Seeders;

use App\Enums\CommissionType;
use App\Enums\RoleType;
use App\Models\CommissionRule;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommissionRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();

        if (!$tenant) {
            $this->command->warn('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $designers = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Designer'))
            ->get();

        $salesUsers = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Sales'))
            ->get();

        if ($designers->isEmpty() || $salesUsers->isEmpty()) {
            $this->command->warn('Please ensure designers and sales users exist before seeding commission rules.');
            return;
        }

        // Create sales commission rules
        foreach ($salesUsers as $sales) {
            CommissionRule::create([
                'tenant_id' => $tenant->id,
                'user_id' => $sales->id,
                'role_type' => RoleType::SALES,
                'type' => CommissionType::PERCENT,
                'percent_rate' => fake()->randomFloat(2, 8, 15),
                'currency' => 'USD',
                'is_active' => true,
            ]);
        }

        // Create designer bonus rules with varied types
        foreach ($designers as $index => $designer) {
            $type = match ($index % 3) {
                0 => CommissionType::FIXED,
                1 => CommissionType::PERCENT,
                2 => CommissionType::HYBRID,
            };

            $ruleData = [
                'tenant_id' => $tenant->id,
                'user_id' => $designer->id,
                'role_type' => RoleType::DESIGNER,
                'type' => $type,
                'currency' => 'USD',
                'is_active' => true,
            ];

            switch ($type) {
                case CommissionType::FIXED:
                    $ruleData['fixed_amount'] = fake()->randomFloat(2, 5, 20);
                    break;
                case CommissionType::PERCENT:
                    $ruleData['percent_rate'] = fake()->randomFloat(2, 3, 10);
                    break;
                case CommissionType::HYBRID:
                    $ruleData['fixed_amount'] = fake()->randomFloat(2, 3, 10);
                    $ruleData['percent_rate'] = fake()->randomFloat(2, 2, 5);
                    break;
            }

            CommissionRule::create($ruleData);
        }

        $this->command->info('Created commission rules for all sales users and designers.');
    }
}

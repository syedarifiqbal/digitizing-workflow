<?php

namespace App\Services;

use App\Enums\CommissionType;
use App\Enums\RoleType;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionCalculator
{
    /**
     * Calculate and create commission for a user on an order
     */
    public function calculateAndCreate(
        Order $order,
        int $userId,
        RoleType $roleType,
        string $earnedOnStatus,
        ?float $extraAmount = null
    ): ?Commission {
        // Find active commission rule for this user and role
        $rule = CommissionRule::where('tenant_id', $order->tenant_id)
            ->where('user_id', $userId)
            ->where('role_type', $roleType)
            ->where('is_active', true)
            ->first();

        $extraAmount = $extraAmount ?? 0;

        // If no rule exists but there's a tip, create a tip-only commission
        if (!$rule) {
            if ($extraAmount > 0 && $roleType === RoleType::DESIGNER) {
                Log::info("No commission rule for user {$userId}, but creating tip-only bonus of {$extraAmount}");

                // Check for existing commission
                $existing = Commission::where('tenant_id', $order->tenant_id)
                    ->where('order_id', $order->id)
                    ->where('user_id', $userId)
                    ->where('role_type', $roleType)
                    ->where('earned_on_status', $earnedOnStatus)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                // Get currency from tenant settings (more reliable than order currency)
                $currency = $order->tenant->getSetting('currency', 'USD');

                return Commission::create([
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'role_type' => $roleType,
                    'base_amount' => 0,
                    'extra_amount' => $extraAmount,
                    'total_amount' => $extraAmount,
                    'currency' => $currency,
                    'earned_on_status' => $earnedOnStatus,
                    'earned_at' => now(),
                    'notes' => "Tip from admin (no commission rule configured)",
                    'rule_snapshot' => [],
                ]);
            }

            Log::info("No active commission rule found for user {$userId}, role {$roleType->value}");
            return null;
        }

        // Calculate base amount based on rule type
        $baseAmount = $this->calculateBaseAmount($rule, $order);

        if ($baseAmount === null) {
            // If we have a tip but can't calculate base, still create commission with tip only
            if ($extraAmount > 0 && $roleType === RoleType::DESIGNER) {
                Log::info("Could not calculate base commission, but creating tip-only bonus of {$extraAmount}");

                $existing = Commission::where('tenant_id', $order->tenant_id)
                    ->where('order_id', $order->id)
                    ->where('user_id', $userId)
                    ->where('role_type', $roleType)
                    ->where('earned_on_status', $earnedOnStatus)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                return Commission::create([
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'role_type' => $roleType,
                    'base_amount' => 0,
                    'extra_amount' => $extraAmount,
                    'total_amount' => $extraAmount,
                    'currency' => $rule->currency,
                    'earned_on_status' => $earnedOnStatus,
                    'earned_at' => now(),
                    'notes' => "Tip from admin (base commission could not be calculated)",
                    'rule_snapshot' => [
                        'type' => $rule->type->value,
                        'fixed_amount' => $rule->fixed_amount,
                        'percent_rate' => $rule->percent_rate,
                        'currency' => $rule->currency,
                    ],
                ]);
            }

            Log::warning("Could not calculate commission - order may be missing price");
            return null;
        }

        // Calculate total
        $totalAmount = $baseAmount + $extraAmount;

        // Check if commission already exists (prevent duplicates)
        $existing = Commission::where('tenant_id', $order->tenant_id)
            ->where('order_id', $order->id)
            ->where('user_id', $userId)
            ->where('role_type', $roleType)
            ->where('earned_on_status', $earnedOnStatus)
            ->first();

        if ($existing) {
            Log::info("Commission already exists for order {$order->id}, user {$userId}, status {$earnedOnStatus}");
            return $existing;
        }

        // Prepare notes for tip
        $notes = null;
        if ($extraAmount > 0 && $roleType === RoleType::DESIGNER) {
            $notes = "Includes {$rule->currency} " . number_format($extraAmount, 2) . " tip from admin";
        }

        // Create commission record
        return Commission::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'user_id' => $userId,
            'role_type' => $roleType,
            'base_amount' => $baseAmount,
            'extra_amount' => $extraAmount,
            'total_amount' => $totalAmount,
            'currency' => $rule->currency,
            'earned_on_status' => $earnedOnStatus,
            'earned_at' => now(),
            'notes' => $notes,
            'rule_snapshot' => [
                'type' => $rule->type->value,
                'fixed_amount' => $rule->fixed_amount,
                'percent_rate' => $rule->percent_rate,
                'currency' => $rule->currency,
            ],
        ]);
    }

    /**
     * Calculate base commission amount based on rule type
     */
    private function calculateBaseAmount(CommissionRule $rule, Order $order): ?float
    {
        $orderPrice = $order->price ?? 0;

        if ($orderPrice <= 0 && $rule->type !== CommissionType::FIXED) {
            return null; // Can't calculate percentage without price
        }

        return match ($rule->type) {
            CommissionType::FIXED => (float) $rule->fixed_amount,
            CommissionType::PERCENT => ($orderPrice * (float) $rule->percent_rate) / 100,
            CommissionType::HYBRID => (float) $rule->fixed_amount + (($orderPrice * (float) $rule->percent_rate) / 100),
        };
    }

    /**
     * Process commissions for an order based on tenant settings
     */
    public function processOrderCommissions(Order $order, string $status, ?float $designerTip = null): void
    {
        $tenant = $order->tenant;

        // Sales commission
        if ($order->sales_user_id) {
            $salesEarnedOn = $tenant->getSetting('sales_commission_earned_on', 'delivered');

            if ($status === $salesEarnedOn) {
                $this->calculateAndCreate(
                    $order,
                    $order->sales_user_id,
                    RoleType::SALES,
                    $status
                );
            }
        }

        // Designer bonus (with optional tip)
        if ($order->designer_id) {
            $designerEarnedOn = $tenant->getSetting('designer_bonus_earned_on', 'delivered');

            if ($status === $designerEarnedOn) {
                $tip = $designerTip ?? $order->getAttribute('pending_designer_tip') ?? 0;

                $this->calculateAndCreate(
                    $order,
                    $order->designer_id,
                    RoleType::DESIGNER,
                    $status,
                    $tip > 0 ? $tip : null
                );
            }
        }
    }

    /**
     * Update commission extra amount (admin override)
     */
    public function updateExtraAmount(Commission $commission, float $extraAmount, ?string $notes = null): Commission
    {
        $commission->update([
            'extra_amount' => $extraAmount,
            'total_amount' => $commission->base_amount + $extraAmount,
            'notes' => $notes ? ($commission->notes ? $commission->notes . "\n" . $notes : $notes) : $commission->notes,
        ]);

        return $commission->fresh();
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_type', ['sales', 'designer']);

            // Amounts
            $table->decimal('base_amount', 10, 2); // Calculated from rule
            $table->decimal('extra_amount', 10, 2)->default(0); // Manual override (mainly for designer bonuses)
            $table->decimal('total_amount', 10, 2); // base + extra
            $table->string('currency', 3)->default('USD');

            // Trigger info
            $table->string('earned_on_status', 50); // Which status triggered this (approved, delivered, etc.)
            $table->timestamp('earned_at')->nullable(); // When the commission was earned

            // Rule snapshot for historical accuracy
            $table->json('rule_snapshot'); // Snapshot of commission rule at time of earning

            // Payment tracking
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();

            // Notes for overrides/adjustments
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'user_id', 'role_type']);
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'is_paid']);
            $table->index('earned_at');

            // Prevent duplicates: one commission per order per user per role per status
            $table->unique(['tenant_id', 'order_id', 'user_id', 'role_type', 'earned_on_status'], 'unique_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};

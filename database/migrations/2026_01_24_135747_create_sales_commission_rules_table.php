<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_type', ['sales', 'designer'])->default('sales');
            $table->enum('type', ['fixed', 'percent', 'hybrid'])->default('fixed');
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->decimal('percent_rate', 5, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'role_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};

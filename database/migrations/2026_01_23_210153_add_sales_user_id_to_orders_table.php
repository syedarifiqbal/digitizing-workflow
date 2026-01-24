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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('sales_user_id')->nullable()->after('designer_id')->constrained('users')->nullOnDelete();
            $table->index(['tenant_id', 'sales_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'sales_user_id']);
            $table->dropForeign(['sales_user_id']);
            $table->dropColumn('sales_user_id');
        });
    }
};

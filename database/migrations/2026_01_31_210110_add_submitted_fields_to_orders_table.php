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
            $table->string('submitted_width')->nullable()->after('delivered_at');
            $table->string('submitted_height')->nullable()->after('submitted_width');
            $table->unsignedInteger('submitted_stitch_count')->nullable()->after('submitted_height');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['submitted_width', 'submitted_height', 'submitted_stitch_count']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Common fields
            $table->string('po_number')->nullable()->after('order_number');

            // Shared: size + placement
            $table->string('height')->nullable()->after('instructions');
            $table->string('width')->nullable()->after('height');
            $table->string('placement')->nullable()->after('width');
            $table->unsignedInteger('num_colors')->nullable()->after('placement');

            // Digitizing-specific
            $table->string('file_format')->nullable()->after('num_colors');

            // Patch-specific
            $table->string('patch_type')->nullable()->after('file_format');
            $table->unsignedInteger('quantity')->nullable()->after('patch_type');
            $table->string('backing')->nullable()->after('quantity');
            $table->string('merrow_border')->nullable()->after('backing');
            $table->string('fabric')->nullable()->after('merrow_border');
            $table->text('shipping_address')->nullable()->after('fabric');
            $table->date('need_by')->nullable()->after('shipping_address');

            // Vector-specific
            $table->string('color_type')->nullable()->after('need_by');
            $table->string('vector_order_type')->nullable()->after('color_type');
            $table->string('required_format')->nullable()->after('vector_order_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'po_number',
                'height',
                'width',
                'placement',
                'num_colors',
                'file_format',
                'patch_type',
                'quantity',
                'backing',
                'merrow_border',
                'fabric',
                'shipping_address',
                'need_by',
                'color_type',
                'vector_order_type',
                'required_format',
            ]);
        });
    }
};

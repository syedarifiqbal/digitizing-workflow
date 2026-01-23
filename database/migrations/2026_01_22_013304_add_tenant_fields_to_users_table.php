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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->boolean('status')->default(1)->after('phone');
            
            // $table->dropUnique('users_email_unique');
            $table->index('tenant_id', 'users_tenant_id_index');

            $table->unique(['tenant_id', 'email'], 'users_tenant_id_email_unique');
            $table->index(['tenant_id', 'status'], 'users_tenant_id_status_index');
            $table->index(['tenant_id', 'created_at'], 'users_tenant_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes/unique first
            $table->dropUnique('users_tenant_id_email_unique');
            $table->dropIndex('users_tenant_id_status_index');
            $table->dropIndex('users_tenant_id_created_at_index');

            // Restore old unique(email)
            $table->unique('email', 'users_email_unique');

            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'phone', 'status']);
        });
    }
};

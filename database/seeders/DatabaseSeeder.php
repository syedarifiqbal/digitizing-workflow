<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            OrderSeeder::class,
            SubmissionSeeder::class,
            RevisionSeeder::class,
            AuditEventSeeder::class,
            CommissionRuleSeeder::class,
            CommissionSeeder::class,
            OrderCommentSeeder::class,
        ]);
    }
}

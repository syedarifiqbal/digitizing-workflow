<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            $this->command->warn('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $orders = Order::where('tenant_id', $tenant->id)
            ->with(['client', 'designer'])
            ->limit(25)
            ->get();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders available for SubmissionSeeder.');
            return;
        }

        $uploader = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'Designer'))
            ->first() ?? User::where('tenant_id', $tenant->id)->first();

        $disk = config('filesystems.default', 'local');

        foreach ($orders as $order) {
            // Input file
            $inputPath = "tenants/{$tenant->id}/orders/{$order->id}/input/sample-{$order->id}.txt";
            Storage::disk($disk)->put($inputPath, "Sample input instructions for {$order->order_number}");

            OrderFile::create([
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'uploaded_by_user_id' => $order->client?->user?->id ?? $uploader?->id,
                'type' => 'input',
                'disk' => $disk,
                'path' => $inputPath,
                'original_name' => "upload-{$order->order_number}.txt",
                'mime_type' => 'text/plain',
                'size' => strlen("Sample input instructions for {$order->order_number}"),
            ]);

            // Output files for submitted+ orders
            if (in_array($order->status, [
                OrderStatus::SUBMITTED,
                OrderStatus::IN_REVIEW,
                OrderStatus::APPROVED,
                OrderStatus::DELIVERED,
                OrderStatus::CLOSED,
            ], true)) {
                $outputPath = "tenants/{$tenant->id}/orders/{$order->id}/output/mock-{$order->id}.dst";
                Storage::disk($disk)->put($outputPath, Str::random(120));

                OrderFile::create([
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'uploaded_by_user_id' => $order->designer?->id ?? $uploader?->id,
                    'type' => 'output',
                    'disk' => $disk,
                    'path' => $outputPath,
                    'original_name' => "design-{$order->order_number}.dst",
                    'mime_type' => 'application/octet-stream',
                    'size' => 120,
                ]);
            }
        }

        $this->command->info('Sample input/output files seeded for demo orders.');
    }
}


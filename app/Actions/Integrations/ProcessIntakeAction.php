<?php

namespace App\Actions\Integrations;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Client;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ProcessIntakeAction
{
    public function execute(Tenant $tenant, array $payload): array
    {
        return DB::transaction(function () use ($tenant, $payload) {
            $clientPayload = Arr::get($payload, 'client', []);
            $orderPayload = Arr::get($payload, 'order', []);

        $client = Client::firstOrNew([
            'tenant_id' => $tenant->id,
            'email' => $clientPayload['email'],
        ]);

            $client->fill([
                'name' => $clientPayload['name'],
                'company' => Arr::get($clientPayload, 'company'),
                'phone' => Arr::get($clientPayload, 'phone'),
                'notes' => Arr::get($clientPayload, 'notes'),
                'is_active' => true,
            ]);

            $client->tenant_id = $tenant->id;
            $client->save();

            $clientUserCreated = false;
            $clientUser = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('email', $clientPayload['email'])
                ->first();

            if (! $clientUser) {
                $clientUser = User::create([
                    'tenant_id' => $tenant->id,
                    'client_id' => $client->id,
                    'name' => $clientPayload['name'],
                    'email' => $clientPayload['email'],
                    'password' => Str::random(32),
                    'is_active' => true,
                ]);

                $clientUser->assignRole('Client');
                Password::broker()->sendResetLink(['email' => $clientUser->email]);
                $clientUserCreated = true;
            } else {
                // ensure client_id is synced
                if ($clientUser->client_id !== $client->id) {
                    $clientUser->update(['client_id' => $client->id]);
                }
            }

        $order = $this->createOrder($tenant, $client, $clientUser, $orderPayload);

        return [
            'message' => 'Order created successfully.',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'client_id' => $client->id,
                'client_user_created' => $clientUserCreated,
            ];
        });
    }

    protected function createOrder(Tenant $tenant, Client $client, User $clientUser, array $orderPayload): Order
    {
        $prefix = $tenant->getSetting('order_number_prefix', '');
        $lastSequence = Order::where('tenant_id', $tenant->id)->max('sequence') ?? 0;
        $sequence = $lastSequence + 1;
        // Keep order numbers predictable for downstream systems
        $orderNumber = $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

        $priorityValue = strtolower($orderPayload['priority'] ?? OrderPriority::NORMAL->value);
        $priority = OrderPriority::tryFrom($priorityValue) ?? OrderPriority::NORMAL;

        $typeValue = strtolower($orderPayload['type'] ?? OrderType::DIGITIZING->value);
        $type = OrderType::tryFrom($typeValue) ?? OrderType::DIGITIZING;

        // Fall back to any tenant user if no admin exists (seed data always has one)
        $adminUser = $tenant->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
            ->first()
            ?? $tenant->users()->first();

        if (! $adminUser) {
            throw new \RuntimeException('No tenant user available to assign as order creator.');
        }

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'created_by' => $adminUser->id,
            'designer_id' => null,
            'order_number' => $orderNumber,
            'sequence' => $sequence,
            'title' => $orderPayload['title'],
            'instructions' => $orderPayload['instructions'] ?? null,
            'status' => OrderStatus::RECEIVED,
            'priority' => $priority,
            'type' => $type,
            'is_quote' => (bool) ($orderPayload['is_quote'] ?? false),
            'due_at' => $orderPayload['due_at'] ?? null,
            'price' => $orderPayload['price_amount'] ?? null,
            'currency' => strtoupper($orderPayload['currency'] ?? $tenant->getSetting('currency', 'USD')),
            'source' => $orderPayload['source'] ?? 'api',
        ]);

        return $order;
    }
}

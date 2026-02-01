<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\WebhookLog;

class WebhookDispatcher
{
    public function dispatch(Order $order, string $event): void
    {
        $tenant = $order->tenant;

        if (! $this->shouldDispatch($tenant, $event)) {
            return;
        }

        $url = $tenant->getSetting('webhook_url');
        $payload = $this->buildPayload($order, $event);

        $log = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'event' => $event,
            'url' => $url,
            'payload' => $payload,
        ]);

        SendWebhookJob::dispatch($log);
    }

    public function dispatchTest(Tenant $tenant): WebhookLog
    {
        $url = $tenant->getSetting('webhook_url');

        $payload = [
            'event' => 'webhook.test',
            'tenant_id' => $tenant->id,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook from Digitizing Workflow.',
            ],
        ];

        $log = WebhookLog::create([
            'tenant_id' => $tenant->id,
            'event' => 'webhook.test',
            'url' => $url,
            'payload' => $payload,
        ]);

        SendWebhookJob::dispatchSync($log);

        return $log->fresh();
    }

    private function shouldDispatch(Tenant $tenant, string $event): bool
    {
        $url = $tenant->getSetting('webhook_url');
        if (empty($url)) {
            return false;
        }

        $enabledEvents = $tenant->getSetting('webhook_events', []);

        return in_array($event, $enabledEvents, true);
    }

    private function buildPayload(Order $order, string $event): array
    {
        return [
            'event' => $event,
            'tenant_id' => $order->tenant_id,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'status' => $order->status->value,
                'type' => $order->type->value,
                'priority' => $order->priority->value,
                'client_id' => $order->client_id,
                'client_name' => $order->client?->name,
                'designer_id' => $order->designer_id,
                'price_amount' => $order->price_amount,
                'currency' => $order->currency,
                'created_at' => $order->created_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
            ],
        ];
    }
}

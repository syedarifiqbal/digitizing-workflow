<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public WebhookLog $webhookLog
    ) {}

    public function handle(): void
    {
        $log = $this->webhookLog;
        $tenant = $log->tenant;
        $secret = $tenant->getSetting('webhook_secret', '');

        $payloadJson = json_encode($log->payload);
        $signature = hash_hmac('sha256', $payloadJson, $secret);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Signature' => $signature,
                    'X-Webhook-Event' => $log->event,
                    'User-Agent' => 'DigitizingWorkflow/1.0',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($log->url);

            $log->update([
                'attempts' => $log->attempts + 1,
                'response_status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 2000),
                'success' => $response->successful(),
            ]);

            if (! $response->successful()) {
                $this->handleFailedAttempt($log, "HTTP {$response->status()}");
            }
        } catch (\Throwable $e) {
            $log->update([
                'attempts' => $log->attempts + 1,
                'response_body' => mb_substr($e->getMessage(), 0, 2000),
                'success' => false,
            ]);

            $this->handleFailedAttempt($log, $e->getMessage());
        }
    }

    private function handleFailedAttempt(WebhookLog $log, string $reason): void
    {
        if ($log->attempts < $this->tries) {
            $this->release($this->backoff[$log->attempts - 1] ?? 300);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Automation;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class N8nWebhookClient
{
    /**
     * Send a domain integration event to the configured n8n webhook.
     *
     * @param array<string, mixed> $data
     * @return array{event_id: string, accepted: bool, status: int|null}
     */
    public function send(string $eventType, array $data, ?string $eventId = null): array
    {
        if (! (bool) config('services.n8n.enabled')) {
            return ['event_id' => $eventId ?? (string) Str::uuid(), 'accepted' => false, 'status' => null];
        }

        $url = config('services.n8n.webhook_url');
        $secret = config('services.n8n.webhook_secret');
        $timeout = max(1, min(30, (int) config('services.n8n.timeout', 10)));

        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('N8N_WEBHOOK_URL must be a valid URL when n8n integration is enabled.');
        }

        if (! is_string($secret) || strlen($secret) < 32) {
            throw new RuntimeException('N8N_WEBHOOK_SECRET must contain at least 32 characters when n8n integration is enabled.');
        }

        $eventId ??= (string) Str::uuid();
        $payload = [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'occurred_at' => now()->toISOString(),
            'data' => $data,
        ];

        /** @var PendingRequest $request */
        $request = Http::acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->connectTimeout(min(5, $timeout))
            ->withHeaders([
                'X-Automation-Secret' => $secret,
                'X-Automation-Event-Id' => $eventId,
            ]);

        $response = $request->post($url, $payload)->throw();

        return [
            'event_id' => $eventId,
            'accepted' => true,
            'status' => $response->status(),
        ];
    }
}

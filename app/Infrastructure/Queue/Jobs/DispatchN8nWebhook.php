<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Automation\N8nWebhookClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class DispatchN8nWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 180];

    public int $timeout = 20;

    private readonly string $eventId;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $eventType,
        private readonly array $data,
    ) {
        $this->eventId = (string) Str::uuid();
        $this->onQueue('default');
    }

    public function handle(N8nWebhookClient $client): void
    {
        if (! (bool) config('services.n8n.enabled')) {
            return;
        }

        $result = $client->send($this->eventType, $this->data, $this->eventId);

        Log::info('n8n webhook event delivered.', [
            'event_id' => $result['event_id'],
            'event_type' => $this->eventType,
            'http_status' => $result['status'],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('n8n webhook delivery failed after all attempts.', [
            'event_id' => $this->eventId,
            'event_type' => $this->eventType,
            'exception' => $exception->getMessage(),
        ]);
    }
}

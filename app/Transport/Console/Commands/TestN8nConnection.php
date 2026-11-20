<?php

declare(strict_types=1);

namespace App\Transport\Console\Commands;

use App\Infrastructure\Automation\N8nWebhookClient;
use Illuminate\Console\Command;
use Throwable;

final class TestN8nConnection extends Command
{
    protected $signature = 'social:n8n:test';

    protected $description = 'Send a test event to the configured n8n webhook';

    public function handle(N8nWebhookClient $client): int
    {
        if (! (bool) config('services.n8n.enabled')) {
            $this->error('n8n integration is disabled. Set N8N_ENABLED=true first.');

            return self::FAILURE;
        }

        try {
            $result = $client->send('integration.test', [
                'application' => (string) config('app.name'),
                'message' => 'KI-SOCIAL-MEDIA can reach n8n.',
            ]);

            $this->info('n8n accepted the test event.');
            $this->line('Event ID: '.$result['event_id']);
            $this->line('HTTP status: '.$result['status']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('n8n test failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}

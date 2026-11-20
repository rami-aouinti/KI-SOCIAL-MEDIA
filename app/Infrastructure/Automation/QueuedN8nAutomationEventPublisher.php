<?php

declare(strict_types=1);

namespace App\Infrastructure\Automation;

use App\Application\Contracts\AutomationEventPublisherInterface;
use App\Infrastructure\Queue\Jobs\DispatchN8nWebhook;

final class QueuedN8nAutomationEventPublisher implements AutomationEventPublisherInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $eventType, array $data): void
    {
        DispatchN8nWebhook::dispatch($eventType, $data);
    }
}

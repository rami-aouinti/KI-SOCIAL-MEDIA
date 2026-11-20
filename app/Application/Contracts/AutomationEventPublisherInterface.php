<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface AutomationEventPublisherInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $eventType, array $data): void;
}

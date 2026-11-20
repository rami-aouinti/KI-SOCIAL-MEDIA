<?php

declare(strict_types=1);

namespace App\Domain\Events;

interface WorkflowEventPublisherInterface
{
    public function publish(ContentWorkflowEvent $event): void;
}

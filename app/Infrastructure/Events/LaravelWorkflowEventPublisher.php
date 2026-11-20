<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Domain\Events\ContentApprovedEvent;
use App\Domain\Events\ContentHandedOffEvent;
use App\Domain\Events\ContentRejectedEvent;
use App\Domain\Events\ContentWorkflowEvent;
use App\Domain\Events\WorkflowEventPublisherInterface;
use App\Transport\Events\ContentApproved;
use App\Transport\Events\ContentHandedOff;
use App\Transport\Events\ContentRejected;
use LogicException;

final class LaravelWorkflowEventPublisher implements WorkflowEventPublisherInterface
{
    public function publish(ContentWorkflowEvent $event): void
    {
        $versionId = $event->contentVersionId();

        match (true) {
            $event instanceof ContentApprovedEvent => ContentApproved::dispatch($versionId),
            $event instanceof ContentRejectedEvent => ContentRejected::dispatch($versionId),
            $event instanceof ContentHandedOffEvent => ContentHandedOff::dispatch($versionId),
            default => throw new LogicException('Unsupported workflow event.'),
        };
    }
}

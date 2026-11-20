<?php

declare(strict_types=1);

namespace App\Domain\Events;

final readonly class ContentRejectedEvent implements ContentWorkflowEvent
{
    public function __construct(private int $versionId)
    {
    }

    public function contentVersionId(): int
    {
        return $this->versionId;
    }
}

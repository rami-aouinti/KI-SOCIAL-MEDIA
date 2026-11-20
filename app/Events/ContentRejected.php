<?php

declare(strict_types=1);

namespace App\Events;

use App\Contracts\Events\ContentWorkflowEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class ContentRejected implements ContentWorkflowEvent, ShouldDispatchAfterCommit
{
    public function __construct(public int $versionId)
    {
    }

    public function contentVersionId(): int
    {
        return $this->versionId;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Domain\Events\ContentWorkflowEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ContentApproved implements ContentWorkflowEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    public function __construct(public int $versionId)
    {
    }

    public function contentVersionId(): int
    {
        return $this->versionId;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Events;

interface ContentWorkflowEvent
{
    public function contentVersionId(): int;
}

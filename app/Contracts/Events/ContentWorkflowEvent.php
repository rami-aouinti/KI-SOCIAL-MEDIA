<?php

declare(strict_types=1);

namespace App\Contracts\Events;

interface ContentWorkflowEvent
{
    public function contentVersionId(): int;
}

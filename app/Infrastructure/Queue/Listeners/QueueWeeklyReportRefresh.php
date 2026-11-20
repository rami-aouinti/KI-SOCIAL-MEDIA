<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Listeners;

use App\Domain\Events\ContentWorkflowEvent;
use App\Infrastructure\Queue\Jobs\RefreshWeeklyReportForContentVersion;

final class QueueWeeklyReportRefresh
{
    public function handle(ContentWorkflowEvent $event): void
    {
        RefreshWeeklyReportForContentVersion::dispatch($event->contentVersionId());
    }
}

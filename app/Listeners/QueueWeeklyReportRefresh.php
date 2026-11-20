<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\Events\ContentWorkflowEvent;
use App\Jobs\RefreshWeeklyReportForContentVersion;

final class QueueWeeklyReportRefresh
{
    public function handle(ContentWorkflowEvent $event): void
    {
        RefreshWeeklyReportForContentVersion::dispatch($event->contentVersionId());
    }
}

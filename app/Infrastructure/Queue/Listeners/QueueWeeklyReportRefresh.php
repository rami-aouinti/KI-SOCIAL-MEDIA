<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Listeners;

use App\Contracts\Events\ContentWorkflowEvent;
use App\Jobs\RefreshWeeklyReportForContentVersion;

final class QueueWeeklyReportRefresh
{
    public function handle(ContentWorkflowEvent $event): void
    {
        RefreshWeeklyReportForContentVersion::dispatch($event->contentVersionId());
    }
}

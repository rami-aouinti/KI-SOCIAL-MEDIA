<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Queue\Jobs\RefreshWeeklyReportForContentVersion;
use App\Transport\Events\ContentApproved;
use App\Transport\Events\ContentHandedOff;
use App\Transport\Events\ContentRejected;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentWorkflowEventsTest extends TestCase
{
    public function test_approval_event_queues_a_weekly_report_refresh(): void
    {
        $this->assertQueuesReportRefresh(new ContentApproved(101));
    }

    public function test_rejection_event_queues_a_weekly_report_refresh(): void
    {
        $this->assertQueuesReportRefresh(new ContentRejected(102));
    }

    public function test_handoff_event_queues_a_weekly_report_refresh(): void
    {
        $this->assertQueuesReportRefresh(new ContentHandedOff(103));
    }

    private function assertQueuesReportRefresh(object $event): void
    {
        Queue::fake();

        event($event);

        Queue::assertPushed(
            RefreshWeeklyReportForContentVersion::class,
            fn (RefreshWeeklyReportForContentVersion $job): bool => $job->contentVersionId === $event->contentVersionId(),
        );
    }
}

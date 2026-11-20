<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Queue\Jobs\RefreshWeeklyReportForContentVersion;
use PHPUnit\Framework\TestCase;

class QueueJobReliabilityTest extends TestCase
{
    public function test_weekly_report_refresh_job_has_bounded_retries_and_duplicate_suppression(): void
    {
        $job = new RefreshWeeklyReportForContentVersion(42);

        self::assertSame(3, $job->tries);
        self::assertSame(60, $job->timeout);
        self::assertSame(300, $job->uniqueFor);
        self::assertSame([10, 30, 90], $job->backoff());
        self::assertSame('weekly-report-refresh:42', $job->uniqueId());
    }
}

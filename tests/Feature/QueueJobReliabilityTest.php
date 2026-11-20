<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Queue\Jobs\RefreshWeeklyReportForContentVersion;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

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

    public function test_permanently_failed_report_refresh_is_logged_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Weekly report refresh job failed permanently',
                Mockery::on(static fn (array $context): bool => $context['content_version_id'] === 42
                    && $context['attempts'] === 0
                    && $context['exception'] === RuntimeException::class
                    && $context['message'] === 'Database unavailable'),
            );

        (new RefreshWeeklyReportForContentVersion(42))->failed(
            new RuntimeException('Database unavailable'),
        );
    }
}

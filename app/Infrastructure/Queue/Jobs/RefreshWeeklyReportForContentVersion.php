<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Jobs;

use App\Application\Services\WeeklyReportGenerator;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RefreshWeeklyReportForContentVersion implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 300;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function uniqueId(): string
    {
        return 'weekly-report-refresh:'.$this->contentVersionId;
    }

    public function __construct(public readonly int $contentVersionId) {}

    public function handle(WeeklyReportGenerator $generator): void
    {
        $version = ContentVersion::query()
            ->with('briefing.contentIdea.weeklyPlan')
            ->find($this->contentVersionId);

        $weeklyPlan = $version?->briefing?->contentIdea?->weeklyPlan;

        if ($weeklyPlan !== null) {
            $generator->store((int) $weeklyPlan->getKey());
        }
    }
}

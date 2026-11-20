<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\WeeklyReportDataSourceInterface;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;

final class EloquentWeeklyReportDataSource implements WeeklyReportDataSourceInterface
{
    public function load(mixed $weeklyPlan): WeeklyPlan
    {
        if (! $weeklyPlan instanceof WeeklyPlan) {
            throw new \InvalidArgumentException('A persisted weekly plan is required.');
        }

        return $weeklyPlan->loadMissing([
            'inspirations',
            'contentIdeas.workAllocations',
            'contentIdeas.briefing.versions.approvals',
            'contentIdeas.briefing.versions.handoffRecords',
        ]);
    }
}

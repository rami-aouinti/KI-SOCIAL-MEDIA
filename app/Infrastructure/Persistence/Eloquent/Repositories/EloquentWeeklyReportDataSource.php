<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\WeeklyReportDataSourceInterface;
use App\Application\DTO\WeeklyReportData;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;

final class EloquentWeeklyReportDataSource implements WeeklyReportDataSourceInterface
{
    public function find(int $weeklyPlanId): WeeklyReportData
    {
        $plan = WeeklyPlan::query()
            ->with([
                'inspirations',
                'contentIdeas.workAllocations',
                'contentIdeas.briefing.latestVersion.latestApproval',
                'contentIdeas.briefing.latestVersion.latestHandoff',
            ])
            ->findOrFail($weeklyPlanId);

        $inspirations = $plan->inspirations
            ->map(static fn ($reference): array => [
                'channel' => $reference->channel,
                'source_id' => $reference->source_id,
                'description' => $reference->description,
                'metrics' => $reference->metrics,
            ])
            ->all();

        $ideas = $plan->contentIdeas
            ->map(function ($idea): array {
                $version = $idea->briefing?->latestVersion;
                $approval = $version?->latestApproval;
                $handoff = $version?->latestHandoff;

                return [
                    'title' => $idea->title,
                    'planned_at' => $idea->planned_at->toDateString(),
                    'channels' => $idea->channels ?? [],
                    'responsible_person' => $idea->responsible_person,
                    'estimated_minutes' => (int) $idea->estimated_minutes,
                    'priority_type' => $this->enumValue($idea->priority_type),
                    'status' => $this->enumValue($idea->status),
                    'selection_reason' => $idea->selection_reason,
                    'allocations' => $idea->workAllocations
                        ->map(static fn ($allocation): array => [
                            'person_key' => $allocation->person_key,
                            'estimated_minutes' => (int) $allocation->estimated_minutes,
                        ])
                        ->all(),
                    'latest_version' => $version ? [
                        'version_number' => (int) $version->version_number,
                        'status' => $this->enumValue($version->status),
                        'approval' => $approval ? [
                            'decision' => $this->enumValue($approval->decision),
                            'reviewer' => $approval->reviewer,
                            'reviewed_at' => $approval->reviewed_at->toDateTimeString(),
                        ] : null,
                        'handoff' => $handoff ? [
                            'status' => $this->enumValue($handoff->status),
                            'handed_off_at' => $handoff->handed_off_at->toDateTimeString(),
                        ] : null,
                    ] : null,
                ];
            })
            ->all();

        return new WeeklyReportData(
            weekStart: $plan->week_start->toDateString(),
            seasonalOccasion: $plan->seasonal_occasion,
            status: $this->enumValue($plan->status),
            capacityLimits: $plan->capacity_limits ?? [],
            inspirations: $inspirations,
            ideas: $ideas,
        );
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}

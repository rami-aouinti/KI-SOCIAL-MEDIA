<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Contracts\DashboardDataSourceInterface;
use App\Application\DTO\DashboardData;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;

final class EloquentDashboardDataSource implements DashboardDataSourceInterface
{
    public function load(): DashboardData
    {
        $plan = WeeklyPlan::query()
            ->with([
                'inspirations',
                'contentIdeas.workAllocations',
                'contentIdeas.briefing.latestVersion.latestApproval',
                'contentIdeas.briefing.latestVersion.latestHandoff',
            ])
            ->latest('week_start')
            ->first();

        if ($plan === null) {
            return new DashboardData(null);
        }

        $ideas = $plan->contentIdeas
            ->sortBy('planned_at')
            ->values()
            ->map(static function ($idea): array {
                $briefing = $idea->briefing;
                $version = $briefing?->latestVersion;
                $approval = $version?->latestApproval;
                $handoff = $version?->latestHandoff;

                return [
                    'id' => $idea->id,
                    'title' => $idea->title,
                    'priority' => $idea->priority_type->value,
                    'status' => str_replace('_', ' ', $idea->status->value),
                    'planned_at' => $idea->planned_at?->format('D, d M'),
                    'channels' => $idea->channels ?? [],
                    'responsible_person' => $idea->responsible_person,
                    'estimated_minutes' => $idea->estimated_minutes,
                    'selection_reason' => $idea->selection_reason,
                    'briefing_status' => $briefing?->status?->value,
                    'briefing' => $briefing === null ? null : [
                        'hook' => $briefing->hook,
                        'filming_plan' => $briefing->filming_plan,
                    ],
                    'channel_variants' => $version?->snapshot['channel_variants'] ?? [],
                    'approval' => $approval?->decision?->value ?? 'Not recorded',
                    'handoff' => $handoff?->status ?? 'Not handed off',
                    'has_handoff' => $handoff !== null,
                ];
            })
            ->all();

        $inspirations = $plan->inspirations->map(static fn ($reference): array => [
            'channel' => ucfirst($reference->channel),
            'source_id' => $reference->source_id,
            'description' => $reference->description,
            'has_metrics' => $reference->metrics !== null,
        ])->all();

        $featuredIdea = collect($ideas)->firstWhere('title', 'Winter eyewear tips');
        $featured = $featuredIdea !== null && $featuredIdea['briefing'] !== null
            ? $featuredIdea
            : null;

        $allocations = $plan->contentIdeas
            ->flatMap(static fn ($idea) => $idea->workAllocations)
            ->map(static fn ($allocation): array => [
                'person_key' => $allocation->person_key,
                'estimated_minutes' => (int) $allocation->estimated_minutes,
            ])
            ->all();

        return new DashboardData(
            plan: [
                'id' => $plan->id,
                'week_start' => $plan->week_start->format('d M Y'),
                'seasonal_occasion' => $plan->seasonal_occasion,
                'generation_simulated' => (bool) data_get($plan->input_data, 'generation.simulated'),
                'generation_model' => data_get($plan->input_data, 'generation.model'),
                'ideas' => $ideas,
                'priority_count' => count(array_filter($ideas, static fn (array $idea): bool => $idea['priority'] === 'priority')),
                'reserve_count' => count(array_filter($ideas, static fn (array $idea): bool => $idea['priority'] === 'reserve')),
                'inspirations' => $inspirations,
                'featured' => $featured,
                'has_handoff' => $featured['has_handoff'] ?? false,
            ],
            capacityLimits: $plan->capacity_limits ?? [],
            allocations: $allocations,
        );
    }
}

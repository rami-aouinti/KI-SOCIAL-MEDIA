<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\WeeklyPlanWriterInterface;
use App\Application\DTO\WeeklyPlanData;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Support\Facades\DB;

final class EloquentWeeklyPlanWriter implements WeeklyPlanWriterInterface
{
    public function create(WeeklyPlanData $data): object
    {
        return DB::transaction(function () use ($data): WeeklyPlan {
            $plan = WeeklyPlan::query()->create([
                'week_start' => $data->weekStart,
                'seasonal_occasion' => $data->seasonalOccasion,
                'input_data' => $data->inputData,
                'capacity_limits' => $data->capacityLimits,
            ]);

            $plan->inspirations()->createMany($data->inspirationRows());

            foreach ($data->ideas as $ideaData) {
                $idea = $plan->contentIdeas()->create([
                    'title' => $ideaData->title,
                    'topic' => $ideaData->topic,
                    'target_audience' => $ideaData->targetAudience,
                    'channels' => $ideaData->channels,
                    'format' => $ideaData->format,
                    'priority_type' => $ideaData->priorityType,
                    'selection_reason' => $ideaData->selectionReason,
                    'responsible_person' => $ideaData->responsiblePerson,
                    'planned_at' => $ideaData->plannedAt,
                    'estimated_minutes' => $ideaData->estimatedMinutes(),
                ]);

                $idea->workAllocations()->createMany(array_map(
                    static fn ($allocation): array => $allocation->toArray(),
                    $ideaData->allocations,
                ));
            }

            return $plan->load(['inspirations', 'contentIdeas.workAllocations']);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Transport\Http\Controllers\Api\V1;

use App\Infrastructure\Persistence\Eloquent\Models\ContentIdea;
use App\Transport\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ContentIdeaController extends Controller
{
    public function show(int $contentIdea): JsonResponse
    {
        $idea = ContentIdea::query()
            ->with([
                'weeklyPlan:id,week_start,seasonal_occasion',
                'workAllocations',
                'briefing.versions',
            ])
            ->findOrFail($contentIdea);

        return response()->json([
            'data' => [
                'id' => $idea->getKey(),
                'weekly_plan' => [
                    'id' => $idea->weeklyPlan->getKey(),
                    'week_start' => $idea->weeklyPlan->week_start?->toDateString(),
                    'seasonal_occasion' => $idea->weeklyPlan->seasonal_occasion,
                ],
                'title' => $idea->title,
                'topic' => $idea->topic,
                'target_audience' => $idea->target_audience,
                'channels' => $idea->channels,
                'format' => $idea->format,
                'priority_type' => $idea->priority_type->value,
                'selection_reason' => $idea->selection_reason,
                'responsible_person' => $idea->responsible_person,
                'planned_at' => $idea->planned_at?->toDateString(),
                'estimated_minutes' => $idea->estimated_minutes,
                'status' => $idea->status->value,
                'work_allocations' => $idea->workAllocations->map(fn ($allocation): array => [
                    'person_key' => $allocation->person_key,
                    'work_type' => $allocation->work_type->value,
                    'estimated_minutes' => $allocation->estimated_minutes,
                ])->all(),
                'briefing' => $idea->briefing === null ? null : [
                    'id' => $idea->briefing->getKey(),
                    'hook' => $idea->briefing->hook,
                    'body' => $idea->briefing->body,
                    'status' => $idea->briefing->status->value,
                    'versions' => $idea->briefing->versions->map(fn ($version): array => [
                        'id' => $version->getKey(),
                        'version_number' => $version->version_number,
                        'snapshot' => $version->snapshot,
                        'channels' => $version->channels,
                        'scheduled_at' => $version->scheduled_at?->toDateString(),
                        'status' => $version->status->value,
                        'content_hash' => $version->content_hash,
                    ])->all(),
                ],
            ],
        ]);
    }
}

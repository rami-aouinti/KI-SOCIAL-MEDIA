<?php

declare(strict_types=1);

namespace App\Transport\Http\Controllers\Api\V1;

use App\Application\Services\WeeklyPlanGenerator;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use App\Transport\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WeeklyPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = WeeklyPlan::query()
            ->withCount(['inspirations', 'contentIdeas'])
            ->orderByDesc('week_start')
            ->paginate(20);

        return response()->json([
            'data' => $plans->getCollection()->map(fn (WeeklyPlan $plan): array => [
                'id' => $plan->getKey(),
                'week_start' => $plan->week_start?->toDateString(),
                'seasonal_occasion' => $plan->seasonal_occasion,
                'status' => $plan->status->value,
                'inspirations_count' => $plan->inspirations_count,
                'content_ideas_count' => $plan->content_ideas_count,
                'created_at' => $plan->created_at?->toISOString(),
            ])->all(),
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ],
        ]);
    }

    public function show(int $weeklyPlan): JsonResponse
    {
        $plan = WeeklyPlan::query()
            ->with([
                'inspirations',
                'contentIdeas.workAllocations',
                'contentIdeas.briefing.versions',
            ])
            ->findOrFail($weeklyPlan);

        return response()->json(['data' => $this->serializePlan($plan)]);
    }

    public function store(Request $request, WeeklyPlanGenerator $generator): JsonResponse
    {
        $result = $generator->generate($request->all());
        $plan = $result['plan'];

        return response()->json([
            'data' => $this->serializePlan($plan),
            'capacity_report' => $result['capacity_report'],
            'selection_reasons' => $result['selection_reasons'],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(WeeklyPlan $plan): array
    {
        return [
            'id' => $plan->getKey(),
            'week_start' => $plan->week_start?->toDateString(),
            'seasonal_occasion' => $plan->seasonal_occasion,
            'status' => $plan->status->value,
            'input_data' => $plan->input_data,
            'capacity_limits' => $plan->capacity_limits,
            'inspirations' => $plan->inspirations->map(fn ($inspiration): array => [
                'id' => $inspiration->getKey(),
                'channel' => $inspiration->channel,
                'source_id' => $inspiration->source_id,
                'description' => $inspiration->description,
                'metrics' => $inspiration->metrics,
            ])->all(),
            'content_ideas' => $plan->contentIdeas->map(fn ($idea): array => [
                'id' => $idea->getKey(),
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
                    'filming_plan' => $idea->briefing->filming_plan,
                    'on_screen_text' => $idea->briefing->on_screen_text,
                    'cover_idea' => $idea->briefing->cover_idea,
                    'cta_text' => $idea->briefing->cta_text,
                    'appointment_url' => $idea->briefing->appointment_url,
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
            ])->all(),
            'created_at' => $plan->created_at?->toISOString(),
        ];
    }
}

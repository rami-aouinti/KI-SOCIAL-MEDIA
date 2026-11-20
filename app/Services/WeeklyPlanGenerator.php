<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WeeklyPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

class WeeklyPlanGenerator
{
    public function __construct(
        private readonly CapacityPlanner $capacityPlanner,
    ) {
    }

    /**
     * Persist a weekly plan from structured, already-prepared input.
     *
     * This service does not invent ideas or claim that inspirations are trends.
     * It validates the supplied five ideas, stores their provenance and task
     * allocations, then asks CapacityPlanner to calculate the workload.
     *
     * @param array<string, mixed> $input
     * @return array{
     *     plan: WeeklyPlan,
     *     capacity_report: array<string, mixed>,
     *     selection_reasons: array<int, array{title: string, priority_type: string, reason: string}>
     * }
     */
    public function generate(array $input): array
    {
        $validator = Validator::make($input, [
            'week_start' => ['required', 'date_format:Y-m-d'],
            'seasonal_occasion' => ['nullable', 'string', 'max:150'],
            'capacity_limits' => ['required', 'array', 'min:1'],
            'capacity_limits.*' => ['required', 'integer', 'min:0'],
            'inspirations' => ['required', 'array', 'min:3'],
            'inspirations.*.channel' => ['required', 'string', 'in:instagram,facebook,tiktok,linkedin'],
            'inspirations.*.source_id' => ['required', 'string', 'max:100'],
            'inspirations.*.description' => ['required', 'string'],
            'inspirations.*.metrics' => ['nullable', 'array'],
            'ideas' => ['required', 'array', 'size:5'],
            'ideas.*.title' => ['required', 'string', 'max:150'],
            'ideas.*.topic' => ['required', 'string'],
            'ideas.*.target_audience' => ['required', 'string', 'max:150'],
            'ideas.*.channels' => ['required', 'array', 'min:1'],
            'ideas.*.channels.*' => ['required', 'string', 'in:instagram,facebook,tiktok,linkedin'],
            'ideas.*.format' => ['required', 'string', 'max:50'],
            'ideas.*.priority_type' => ['required', 'string', 'in:priority,reserve'],
            'ideas.*.selection_reason' => ['required', 'string'],
            'ideas.*.responsible_person' => ['required', 'string', 'max:30'],
            'ideas.*.planned_at' => ['required', 'date_format:Y-m-d'],
            'ideas.*.allocations' => ['required', 'array', 'min:1'],
            'ideas.*.allocations.*.person_key' => ['required', 'string', 'max:30'],
            'ideas.*.allocations.*.work_type' => ['required', 'string', 'in:production,variants,coordination,approval'],
            'ideas.*.allocations.*.estimated_minutes' => ['required', 'integer', 'min:1'],
        ]);

        $validator->after(function (LaravelValidator $validator) use ($input): void {
            $weekStart = $input['week_start'] ?? null;

            if (is_string($weekStart)) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $weekStart);

                if ($date !== false && $date->format('N') !== '1') {
                    $validator->errors()->add('week_start', 'The week must start on a Monday.');
                }
            }

            $ideas = $input['ideas'] ?? [];

            if (! is_array($ideas)) {
                return;
            }

            $priorities = count(array_filter(
                $ideas,
                static fn (mixed $idea): bool => is_array($idea)
                    && ($idea['priority_type'] ?? null) === 'priority'
            ));
            $reserves = count(array_filter(
                $ideas,
                static fn (mixed $idea): bool => is_array($idea)
                    && ($idea['priority_type'] ?? null) === 'reserve'
            ));

            if ($priorities !== 3) {
                $validator->errors()->add('ideas', 'A weekly plan must contain exactly three priority ideas.');
            }

            if ($reserves !== 2) {
                $validator->errors()->add('ideas', 'A weekly plan must contain exactly two reserve ideas.');
            }
        });

        $validated = $validator->validate();

        return DB::transaction(function () use ($validated): array {
            $plan = WeeklyPlan::create([
                'week_start' => $validated['week_start'],
                'seasonal_occasion' => $validated['seasonal_occasion'] ?? null,
                'input_data' => $validated,
                'capacity_limits' => $validated['capacity_limits'],
            ]);

            $plan->inspirations()->createMany($validated['inspirations']);

            foreach ($validated['ideas'] as $ideaData) {
                $allocations = $ideaData['allocations'];
                $estimatedMinutes = array_sum(array_column($allocations, 'estimated_minutes'));

                $idea = $plan->contentIdeas()->create([
                    'title' => $ideaData['title'],
                    'topic' => $ideaData['topic'],
                    'target_audience' => $ideaData['target_audience'],
                    'channels' => $ideaData['channels'],
                    'format' => $ideaData['format'],
                    'priority_type' => $ideaData['priority_type'],
                    'selection_reason' => $ideaData['selection_reason'],
                    'responsible_person' => $ideaData['responsible_person'],
                    'planned_at' => $ideaData['planned_at'],
                    'estimated_minutes' => $estimatedMinutes,
                ]);

                $idea->workAllocations()->createMany($allocations);
            }

            $plan->load(['inspirations', 'contentIdeas.workAllocations']);

            return [
                'plan' => $plan,
                'capacity_report' => $this->capacityPlanner->analyze($plan),
                'selection_reasons' => $plan->contentIdeas
                    ->map(static fn ($idea): array => [
                        'title' => $idea->title,
                        'priority_type' => $idea->priority_type->value,
                        'reason' => $idea->selection_reason,
                    ])
                    ->all(),
            ];
        });
    }
}

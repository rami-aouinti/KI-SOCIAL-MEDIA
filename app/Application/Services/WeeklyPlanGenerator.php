<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\WeeklyPlanWriterInterface;
use App\Application\DTO\WeeklyPlanData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

class WeeklyPlanGenerator
{
    public function __construct(
        private readonly CapacityPlanner $capacityPlanner,
        private readonly WeeklyPlanWriterInterface $plans,
    ) {}

    /**
     * Persist a structured plan after validating user input and generated ideas.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     plan: mixed,
     *     capacity_report: array<string, mixed>,
     *     selection_reasons: array<int, array{title: string, priority_type: string, reason: string}>
     * }
     */
    public function generate(array $input): array
    {
        $validator = Validator::make($input, [
            'week_start' => ['required', 'date_format:Y-m-d'],
            'seasonal_occasion' => ['nullable', 'string', 'max:150'],
            'themes' => ['nullable', 'array'],
            'themes.*' => ['string', 'max:200'],
            'tone' => ['nullable', 'string', 'max:100'],
            'target_audience' => ['nullable', 'string', 'max:150'],
            'brand_context' => ['nullable', 'string', 'max:500'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'in:instagram,facebook,tiktok,linkedin'],
            'generation' => ['nullable', 'array'],
            'generation.provider' => ['required_with:generation', 'string', 'max:50'],
            'generation.simulated' => ['required_with:generation', 'boolean'],
            'generation.model' => ['nullable', 'string', 'max:100'],
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

            $priorityTypes = array_column(array_filter($ideas, 'is_array'), 'priority_type');
            $priorities = count(array_filter(
                $priorityTypes,
                static fn (mixed $priorityType): bool => $priorityType === 'priority',
            ));
            $reserves = count(array_filter(
                $priorityTypes,
                static fn (mixed $priorityType): bool => $priorityType === 'reserve',
            ));

            if ($priorities !== 3) {
                $validator->errors()->add('ideas', 'A weekly plan must contain exactly three priority ideas.');
            }

            if ($reserves !== 2) {
                $validator->errors()->add('ideas', 'A weekly plan must contain exactly two reserve ideas.');
            }
        });

        $validated = $validator->validate();
        $planData = WeeklyPlanData::fromValidatedArray($validated);
        $allocations = [];
        $selectionReasons = [];

        foreach ($planData->ideas as $idea) {
            $selectionReasons[] = [
                'title' => $idea->title,
                'priority_type' => $idea->priorityType,
                'reason' => $idea->selectionReason,
            ];

            foreach ($idea->allocations as $allocation) {
                $allocations[] = [
                    'person_key' => $allocation->personKey,
                    'estimated_minutes' => $allocation->estimatedMinutes,
                ];
            }
        }

        $capacityReport = $this->capacityPlanner->analyze($planData->capacityLimits, $allocations);
        $plan = $this->plans->create($planData);

        return [
            'plan' => $plan,
            'capacity_report' => $capacityReport,
            'selection_reasons' => $selectionReasons,
        ];
    }
}

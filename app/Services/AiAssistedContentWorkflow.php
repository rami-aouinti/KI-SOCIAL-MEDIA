<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AiContentGeneratorInterface;
use App\Models\ContentIdea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AiAssistedContentWorkflow
{
    public function __construct(
        private readonly AiContentGeneratorInterface $generator,
        private readonly WeeklyPlanGenerator $weeklyPlanGenerator,
        private readonly ContentVersionCreator $contentVersionCreator,
    ) {
    }

    /**
     * Generate five structured ideas, then pass them through the normal
     * validation, persistence and capacity-planning workflow.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function generateWeeklyPlan(array $input): array
    {
        $generated = $this->generator->generateWeeklyPlan($input);
        $metadata = $this->generationMetadata();

        $result = $this->weeklyPlanGenerator->generate([
            ...$input,
            'ideas' => $generated['ideas'] ?? null,
            'generation' => $metadata,
        ]);

        $result['generation'] = $metadata;

        return $result;
    }

    /**
     * Generate and persist a complete briefing plus its first immutable
     * content version. Approval remains a separate human action.
     *
     * @return array{briefing: \App\Models\ContentBriefing, version: \App\Models\ContentVersion, generation: array<string, mixed>}
     */
    public function generateBriefing(ContentIdea $idea): array
    {
        $idea->loadMissing('weeklyPlan');

        $context = [
            'idea' => [
                'title' => $idea->title,
                'topic' => $idea->topic,
                'target_audience' => $idea->target_audience,
                'channels' => $idea->channels,
                'planned_at' => $idea->planned_at?->format('Y-m-d'),
            ],
            'seasonal_occasion' => $idea->weeklyPlan?->seasonal_occasion,
            'tone' => $idea->weeklyPlan?->input_data['tone'] ?? 'friendly and personal',
        ];

        $generated = $this->generator->generateBriefing($context);
        $validated = Validator::make($generated, [
            'hook' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'filming_plan' => ['required', 'string'],
            'on_screen_text' => ['required', 'array', 'min:1'],
            'on_screen_text.*' => ['required', 'string'],
            'cover_idea' => ['required', 'string'],
            'cta_text' => ['required', 'string'],
            'appointment_url' => ['required', 'url'],
            'channel_variants' => ['required', 'array', 'min:1'],
            'channel_variants.*.text' => ['required', 'string'],
            'media_reference' => ['required', 'string', 'max:500'],
        ])->validate();

        foreach ($idea->channels as $channel) {
            if (! isset($validated['channel_variants'][$channel])) {
                throw ValidationException::withMessages([
                    'channel_variants' => "The generated briefing is missing the {$channel} variant.",
                ]);
            }
        }

        $metadata = $this->generationMetadata();

        return DB::transaction(function () use ($idea, $validated, $metadata): array {
            if ($idea->briefing()->exists()) {
                throw ValidationException::withMessages([
                    'briefing' => 'A briefing already exists. Create a new content version instead of overwriting it.',
                ]);
            }

            $briefing = $idea->briefing()->create([
                'hook' => $validated['hook'],
                'body' => $validated['body'],
                'filming_plan' => $validated['filming_plan'],
                'on_screen_text' => $validated['on_screen_text'],
                'cover_idea' => $validated['cover_idea'],
                'cta_text' => $validated['cta_text'],
                'appointment_url' => $validated['appointment_url'],
                'status' => 'ready',
            ]);

            $version = $this->contentVersionCreator->create($briefing, [
                'hook' => $validated['hook'],
                'body' => $validated['body'],
                'channel_variants' => $validated['channel_variants'],
                'media_reference' => $validated['media_reference'],
                'channels' => $idea->channels,
                'scheduled_at' => $idea->planned_at->format('Y-m-d'),
                'generation' => $metadata,
            ]);

            return [
                'briefing' => $briefing,
                'version' => $version,
                'generation' => $metadata,
            ];
        });
    }

    /**
     * @return array{provider: string, simulated: bool, model: string}
     */
    private function generationMetadata(): array
    {
        return [
            'provider' => 'mock',
            'simulated' => true,
            'model' => 'deterministic-fixture-v1',
        ];
    }
}

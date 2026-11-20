<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\ContentBriefingWriterInterface;
use App\Application\DTO\GeneratedBriefingData;
use App\Application\DTO\GenerationMetadataData;
use App\Domain\Content\AI\AiContentGeneratorInterface;
use App\Domain\Content\Records\ContentBriefingRecord;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Transactions\TransactionManagerInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AiAssistedContentWorkflow
{
    public function __construct(
        private readonly AiContentGeneratorInterface $generator,
        private readonly WeeklyPlanGenerator $weeklyPlanGenerator,
        private readonly ContentVersionCreator $contentVersionCreator,
        private readonly ContentBriefingWriterInterface $briefings,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function generateWeeklyPlan(array $input): array
    {
        $startedAt = hrtime(true);
        $generated = $this->generator->generateWeeklyPlan($input);
        $metadata = $this->generationMetadata($startedAt);
        $result = $this->weeklyPlanGenerator->generate([
            ...$input,
            'ideas' => $generated['ideas'] ?? null,
            'generation' => $metadata,
        ]);
        $result['generation'] = $metadata;

        return $result;
    }

    /**
     * @return array{briefing: ContentBriefingRecord, version: ContentVersionRecord, generation: array<string, mixed>}
     */
    public function generateBriefing(int $ideaId): array
    {
        $idea = $this->briefings->findIdea($ideaId);
        $startedAt = hrtime(true);
        $generated = $this->generator->generateBriefing([
            'idea' => [
                'title' => $idea->title,
                'topic' => $idea->topic,
                'target_audience' => $idea->targetAudience,
                'channels' => $idea->channels,
                'planned_at' => $idea->plannedAt,
            ],
            'seasonal_occasion' => $idea->seasonalOccasion,
            'tone' => $idea->tone,
        ]);

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

        $briefingData = GeneratedBriefingData::fromValidatedArray($validated);

        foreach ($idea->channels as $channel) {
            if (! isset($briefingData->channelVariants[$channel])) {
                throw ValidationException::withMessages([
                    'channel_variants' => "The generated briefing is missing the {$channel} variant.",
                ]);
            }
        }

        $metadata = $this->generationMetadata($startedAt);

        return $this->transactions->run(function () use ($idea, $briefingData, $metadata): array {
            $briefing = $this->briefings->create($idea->id, $briefingData);
            $version = $this->contentVersionCreator->create($briefing->id, [
                ...$briefingData->toArray(),
                'channels' => $idea->channels,
                'scheduled_at' => $idea->plannedAt,
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
     * @return array{provider: string, simulated: bool, model: string, duration_ms: int, cost_estimate: null}
     */
    private function generationMetadata(int $startedAt): array
    {
        return [
            ... (new GenerationMetadataData(
                provider: 'mock',
                simulated: true,
                model: 'deterministic-fixture-v1',
            ))->toArray(),
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'cost_estimate' => null,
        ];
    }
}

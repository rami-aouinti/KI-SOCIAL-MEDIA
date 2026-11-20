<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Services\AiAssistedContentWorkflow;
use App\Application\Services\ContentApprovalService;
use App\Application\Services\SimulatedContentHandoffService;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $weekStart = '2026-11-23';
        $plan = WeeklyPlan::query()->whereDate('week_start', $weekStart)->first();

        if (! $plan) {
            $result = app(AiAssistedContentWorkflow::class)->generateWeeklyPlan([
                'week_start' => $weekStart,
                'seasonal_occasion' => 'Winter eyewear awareness',
                'themes' => [
                    'Practical advice for caring for glasses in cold weather.',
                    'Simple lens-cleaning do and do not guidance.',
                    'A gentle reminder to take regular screen breaks.',
                    'Explain when sunglasses can be useful in winter.',
                    'Show the steps involved in selecting a suitable frame.',
                ],
                'tone' => 'Friendly, personal, practical and easy to understand',
                'target_audience' => 'People who wear glasses',
                'brand_context' => 'Fictional optician offering eyewear and personal vision consultations.',
                'channels' => ['instagram', 'facebook', 'tiktok', 'linkedin'],
                'capacity_limits' => ['person_a' => 90, 'person_b' => 150],
                'inspirations' => [
                    [
                        'channel' => 'instagram',
                        'source_id' => 'synthetic-source-001',
                        'description' => 'Synthetic reference: winter eyewear care educational carousel.',
                        'metrics' => null,
                    ],
                    [
                        'channel' => 'tiktok',
                        'source_id' => 'synthetic-source-002',
                        'description' => 'Synthetic reference: myth-versus-fact video about cold-weather eye comfort.',
                        'metrics' => null,
                    ],
                    [
                        'channel' => 'facebook',
                        'source_id' => 'synthetic-source-003',
                        'description' => 'Synthetic reference: community Q&A about glasses and winter routines.',
                        'metrics' => null,
                    ],
                ],
            ]);

            $plan = $result['plan'];
        }

        $priority = $plan->contentIdeas()
            ->where('title', 'Winter eyewear tips')
            ->first();

        if (! $priority) {
            throw new RuntimeException('The AI demo plan is missing its expected priority idea.');
        }

        $briefing = $priority->briefing;

        if (! $briefing) {
            $generated = app(AiAssistedContentWorkflow::class)->generateBriefing($priority);
            $briefing = $generated['briefing'];
        }

        $version = $briefing->versions()
            ->orderByDesc('version_number')
            ->first();

        if (! $version) {
            throw new RuntimeException('The demo briefing has no generated content version.');
        }

        $approvalService = app(ContentApprovalService::class);

        if ($version->status === ContentVersionStatus::DRAFT) {
            $approvalService->submitForReview((int) $version->getKey());
            $version = $approvalService->approve(
                (int) $version->getKey(),
                'Demo reviewer',
                'Synthetic demonstration approval. AI output reviewed by a human.'
            );
        } elseif ($version->status === ContentVersionStatus::IN_REVIEW) {
            $version = $approvalService->approve(
                (int) $version->getKey(),
                'Demo reviewer',
                'Synthetic demonstration approval. AI output reviewed by a human.'
            );
        }

        // The handoff service is idempotent and never contacts a real platform.
        app(SimulatedContentHandoffService::class)->handoff((int) $version->id);
    }
}

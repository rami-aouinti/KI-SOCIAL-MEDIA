<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Services\AiAssistedContentWorkflow;
use App\Domain\Content\AI\AiContentGeneratorInterface;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\AI\Mock\MockAiContentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiAssistedContentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ai_contract_is_bound_to_the_deterministic_mock_provider(): void
    {
        $this->assertInstanceOf(
            MockAiContentGenerator::class,
            app(AiContentGeneratorInterface::class)
        );
    }

    public function test_mock_ai_generates_a_valid_five_idea_plan_and_persists_provenance(): void
    {
        $result = app(AiAssistedContentWorkflow::class)->generateWeeklyPlan($this->planInput());
        $plan = $result['plan'];

        $this->assertDatabaseCount('weekly_plans', 1);
        $this->assertDatabaseCount('content_ideas', 5);
        $this->assertDatabaseCount('inspiration_references', 3);
        $this->assertSame(3, $plan->contentIdeas->filter(
            static fn ($idea): bool => $idea->priority_type->value === 'priority'
        )->count());
        $this->assertSame(2, $plan->contentIdeas->filter(
            static fn ($idea): bool => $idea->priority_type->value === 'reserve'
        )->count());
        $this->assertSame(
            'Friendly and practical winter eyewear guidance.',
            $plan->contentIdeas->first()->topic
        );
        $this->assertSame('mock', $plan->input_data['generation']['provider']);
        $this->assertTrue($plan->input_data['generation']['simulated']);
        $this->assertTrue($result['capacity_report']['within_capacity']);
        $workTypes = $plan->contentIdeas
            ->flatMap(static fn ($idea) => $idea->workAllocations)
            ->map(static fn ($allocation): string => $allocation->work_type->value)
            ->all();
        $this->assertContains('production', $workTypes);
        $this->assertContains('variants', $workTypes);
        $this->assertContains('coordination', $workTypes);
        $this->assertContains('approval', $workTypes);
    }

    public function test_invalid_ai_plan_output_is_rejected_before_anything_is_persisted(): void
    {
        $this->app->instance(AiContentGeneratorInterface::class, new class implements AiContentGeneratorInterface
        {
            public function generateWeeklyPlan(array $context): array
            {
                return ['ideas' => []];
            }

            public function generateBriefing(array $context): array
            {
                return [];
            }
        });

        try {
            app(AiAssistedContentWorkflow::class)->generateWeeklyPlan($this->planInput());
            $this->fail('Invalid generated output should fail validation.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('weekly_plans', 0);
            $this->assertDatabaseCount('content_ideas', 0);
        }
    }

    public function test_mock_ai_generates_a_complete_briefing_and_immutable_version_for_human_review(): void
    {
        $plan = app(AiAssistedContentWorkflow::class)->generateWeeklyPlan($this->planInput())['plan'];
        $idea = $plan->contentIdeas->firstWhere('title', 'Winter eyewear tips');

        $result = app(AiAssistedContentWorkflow::class)->generateBriefing($idea);

        $this->assertDatabaseCount('content_briefings', 1);
        $this->assertDatabaseCount('content_versions', 1);
        $this->assertSame('ready', $result['briefing']->status->value);
        $this->assertNotEmpty($result['briefing']->filming_plan);
        $this->assertNotEmpty($result['briefing']->on_screen_text);
        $this->assertSame(
            ['instagram', 'tiktok'],
            $result['version']->channels
        );
        $this->assertArrayHasKey('instagram', $result['version']->snapshot['channel_variants']);
        $this->assertArrayHasKey('tiktok', $result['version']->snapshot['channel_variants']);
        $this->assertSame('mock', $result['version']->snapshot['generation']['provider']);
        $this->assertTrue($result['version']->snapshot['generation']['simulated']);
        $this->assertSame(ContentVersionStatus::DRAFT, $result['version']->status);
        $this->assertStringStartsWith('https://example.com/', $result['briefing']->appointment_url);
    }

    public function test_generated_briefing_cannot_be_silently_overwritten(): void
    {
        $plan = app(AiAssistedContentWorkflow::class)->generateWeeklyPlan($this->planInput())['plan'];
        $idea = $plan->contentIdeas->firstWhere('title', 'Winter eyewear tips');
        $workflow = app(AiAssistedContentWorkflow::class);

        $workflow->generateBriefing($idea);

        $this->expectException(ValidationException::class);
        $workflow->generateBriefing($idea);
    }

    /**
     * @return array<string, mixed>
     */
    private function planInput(): array
    {
        return [
            'week_start' => '2026-11-23',
            'seasonal_occasion' => 'Winter eyewear awareness',
            'themes' => [
                'Friendly and practical winter eyewear guidance.',
                'Safe and simple lens-cleaning advice.',
                'Healthy screen-break reminders.',
                'Winter sunglasses use cases.',
                'How to choose a suitable frame.',
            ],
            'tone' => 'Friendly, personal and practical',
            'target_audience' => 'People who wear glasses',
            'brand_context' => 'Fictional optician offering personal consultations.',
            'channels' => ['instagram', 'facebook', 'tiktok', 'linkedin'],
            'capacity_limits' => ['person_a' => 90, 'person_b' => 150],
            'inspirations' => [
                [
                    'channel' => 'instagram',
                    'source_id' => 'synthetic-ai-source-001',
                    'description' => 'Synthetic winter eyewear carousel reference.',
                    'metrics' => null,
                ],
                [
                    'channel' => 'tiktok',
                    'source_id' => 'synthetic-ai-source-002',
                    'description' => 'Synthetic short-video format reference.',
                    'metrics' => null,
                ],
                [
                    'channel' => 'facebook',
                    'source_id' => 'synthetic-ai-source-003',
                    'description' => 'Synthetic community discussion reference.',
                    'metrics' => null,
                ],
            ],
        ];
    }
}

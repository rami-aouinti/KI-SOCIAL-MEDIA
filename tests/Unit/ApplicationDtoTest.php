<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\DTO\ContentIdeaData;
use App\Application\DTO\ContentVersionData;
use App\Application\DTO\GeneratedBriefingData;
use App\Application\DTO\GenerationMetadataData;
use App\Application\DTO\InspirationData;
use App\Application\DTO\WeeklyPlanData;
use PHPUnit\Framework\TestCase;

class ApplicationDtoTest extends TestCase
{
    public function test_weekly_plan_dto_maps_nested_ideas_allocations_and_inspirations(): void
    {
        $data = WeeklyPlanData::fromValidatedArray([
            'week_start' => '2026-11-23',
            'seasonal_occasion' => 'Winter',
            'capacity_limits' => ['person_a' => 90],
            'inspirations' => [[
                'channel' => 'instagram',
                'source_id' => 'source-1',
                'description' => 'Synthetic reference',
                'metrics' => null,
            ]],
            'ideas' => [[
                'title' => 'Lens care',
                'topic' => 'Care tips',
                'target_audience' => 'Adults',
                'channels' => ['instagram'],
                'format' => 'carousel',
                'priority_type' => 'priority',
                'selection_reason' => 'Useful',
                'responsible_person' => 'person_a',
                'planned_at' => '2026-11-24',
                'allocations' => [[
                    'person_key' => 'person_a',
                    'work_type' => 'production',
                    'estimated_minutes' => 25,
                ], [
                    'person_key' => 'person_a',
                    'work_type' => 'approval',
                    'estimated_minutes' => 5,
                ]],
            ]],
        ]);

        self::assertSame('2026-11-23', $data->weekStart);
        self::assertSame(30, $data->ideas[0]->estimatedMinutes());
        self::assertSame('source-1', $data->inspirationRows()[0]['source_id']);
        self::assertInstanceOf(ContentIdeaData::class, $data->ideas[0]);
        self::assertInstanceOf(InspirationData::class, $data->inspirations[0]);
    }

    public function test_content_version_dto_builds_stable_snapshot_and_approval_payload(): void
    {
        $data = ContentVersionData::fromValidatedArray([
            'hook' => 'A hook',
            'body' => 'A body',
            'channel_variants' => ['instagram' => ['text' => 'Caption']],
            'media_reference' => 'media/demo.mp4',
            'channels' => ['instagram', 'instagram'],
            'scheduled_at' => '2026-11-24',
            'generation' => ['provider' => 'mock', 'simulated' => true, 'model' => 'fixture'],
        ]);

        self::assertSame(['instagram'], $data->channels);
        self::assertSame('mock', $data->snapshot()['generation']['provider']);
        self::assertSame('2026-11-24', $data->approvalPayload()['scheduled_at']);
    }

    public function test_generated_briefing_and_generation_metadata_dtos_expose_persistence_shapes(): void
    {
        $briefing = GeneratedBriefingData::fromValidatedArray([
            'hook' => 'Hook',
            'body' => 'Body',
            'filming_plan' => 'Plan',
            'on_screen_text' => ['Tip'],
            'cover_idea' => 'Cover',
            'cta_text' => 'Book',
            'appointment_url' => 'https://example.com',
            'channel_variants' => ['instagram' => ['text' => 'Caption']],
            'media_reference' => 'media/demo.mp4',
        ]);

        $metadata = new GenerationMetadataData('mock', true, 'fixture-v1');

        self::assertSame('Plan', $briefing->toArray()['filming_plan']);
        self::assertSame(true, $metadata->toArray()['simulated']);
    }
}

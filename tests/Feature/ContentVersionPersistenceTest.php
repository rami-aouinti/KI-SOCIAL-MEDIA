<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shared\Enums\ContentBriefingStatus;
use App\Domain\Shared\Enums\ContentIdeaPriority;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ContentBriefing;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use App\Application\Services\ContentVersionCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentVersionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_versioned_snapshot_with_a_sha256_hash(): void
    {
        $briefing = $this->createBriefing();

        $version = app(ContentVersionCreator::class)->create($briefing, $this->versionData());

        $this->assertDatabaseHas('content_versions', [
            'id' => $version->id,
            'content_briefing_id' => $briefing->id,
            'version_number' => 1,
            'status' => 'draft',
        ]);
        $this->assertSame(ContentVersionStatus::DRAFT, $version->status);
        $this->assertSame('Try these winter eyewear tips.', $version->snapshot['hook']);
        $this->assertSame(64, strlen($version->content_hash));
        $this->assertSame(1, $briefing->versions()->count());
    }

    public function test_it_increments_version_number_for_each_new_snapshot(): void
    {
        $briefing = $this->createBriefing();
        $creator = app(ContentVersionCreator::class);

        $first = $creator->create($briefing, $this->versionData());
        $secondData = $this->versionData();
        $secondData['body'] = 'A revised version of the content.';
        $second = $creator->create($briefing, $secondData);

        $this->assertSame(1, $first->version_number);
        $this->assertSame(2, $second->version_number);
        $this->assertNotSame($first->content_hash, $second->content_hash);
    }

    public function test_version_belongs_to_briefing_and_briefing_has_versions(): void
    {
        $briefing = $this->createBriefing();
        $version = app(ContentVersionCreator::class)->create($briefing, $this->versionData());

        $this->assertTrue($version->briefing->is($briefing));
        $this->assertTrue($briefing->versions->first()->is($version));
    }

    public function test_missing_channel_variant_is_rejected_without_creating_version(): void
    {
        $briefing = $this->createBriefing();
        $data = $this->versionData();
        unset($data['channel_variants']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            app(ContentVersionCreator::class)->create($briefing, $data);
        } finally {
            $this->assertDatabaseCount('content_versions', 0);
        }
    }


    public function test_snapshot_fields_cannot_be_changed_after_creation(): void
    {
        $briefing = $this->createBriefing();
        $version = app(ContentVersionCreator::class)->create($briefing, $this->versionData());

        $version->snapshot = ['hook' => 'Changed after approval'];
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Create a new version instead.');

        $version->save();
    }

    public function test_hash_changes_when_schedule_or_media_reference_changes(): void
    {
        $briefing = $this->createBriefing();
        $creator = app(ContentVersionCreator::class);
        $first = $creator->create($briefing, $this->versionData());

        $changed = $this->versionData();
        $changed['scheduled_at'] = '2026-11-26';
        $second = $creator->create($briefing, $changed);

        $this->assertNotSame($first->content_hash, $second->content_hash);
    }

    private function createBriefing(): ContentBriefing
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'input_data' => [],
            'capacity_limits' => ['person_a' => 90, 'person_b' => 150],
        ]);

        $idea = $plan->contentIdeas()->create([
            'title' => 'Winter eyewear tips',
            'topic' => 'Practical winter eyewear advice.',
            'target_audience' => 'Adults interested in eyewear',
            'channels' => ['instagram', 'tiktok'],
            'format' => 'reel',
            'priority_type' => ContentIdeaPriority::PRIORITY,
            'selection_reason' => 'Seasonal and useful.',
            'responsible_person' => 'person_a',
            'planned_at' => '2026-11-25',
            'estimated_minutes' => 40,
        ]);

        return $idea->briefing()->create([
            'hook' => 'Your glasses fog up in winter?',
            'body' => 'A friendly explanation.',
            'filming_plan' => 'Film a close-up demonstration.',
            'on_screen_text' => ['Winter eyewear tips'],
            'cover_idea' => 'Close-up of glasses.',
            'cta_text' => 'Book a consultation.',
            'appointment_url' => 'https://example.com/appointments',
            'status' => ContentBriefingStatus::DRAFT,
        ]);
    }

    /** @return array<string, mixed> */
    private function versionData(): array
    {
        return [
            'hook' => 'Try these winter eyewear tips.',
            'body' => 'Keep your glasses clear with these simple habits.',
            'channel_variants' => [
                'instagram' => [
                    'text' => 'Save these three winter eyewear tips.',
                    'format' => 'carousel',
                ],
                'tiktok' => [
                    'text' => 'Three quick tips to keep your glasses clear.',
                    'format' => 'short_video',
                ],
            ],
            'media_reference' => 'synthetic-media/winter-eyewear-demo.mp4',
            'channels' => ['instagram', 'tiktok'],
            'scheduled_at' => '2026-11-25',
        ];
    }
}

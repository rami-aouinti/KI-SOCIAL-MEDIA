<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Services\ContentApprovalService;
use App\Application\Services\ContentVersionCreator;
use App\Application\Services\SimulatedContentHandoffService;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Domain\Shared\Enums\ContentBriefingStatus;
use App\Domain\Shared\Enums\ContentIdeaPriority;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Domain\Shared\Enums\WorkAllocationType;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersionApproval;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class SimulatedContentHandoffServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_version_is_handed_off_only_once_when_retried(): void
    {
        $version = $this->createApprovedVersion();
        $service = app(SimulatedContentHandoffService::class);
        $first = $service->handoff($version->id);
        $second = $service->handoff($version->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('handed_off', $first->status);
        $this->assertSame('simulated', $first->payload['mode']);
        $this->assertSame('simulated-publishing-interface', $first->payload['destination']);
        $this->assertSame($version->contentHash, $first->payload['content_hash']);
        $this->assertSame(ContentVersionStatus::HANDED_OFF, ContentVersion::query()->findOrFail($version->id)->status);
        $this->assertDatabaseCount('content_handoff_records', 1);
    }

    public function test_handoff_is_blocked_without_an_approval_for_the_exact_version_hash(): void
    {
        $version = $this->createVersion();
        ContentVersion::query()->findOrFail($version->id)->update(['status' => ContentVersionStatus::APPROVED]);

        try {
            app(SimulatedContentHandoffService::class)->handoff($version->id);
            $this->fail('Handoff must require an approval for the exact content hash.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('approval', $exception->errors());
        }

        $this->assertDatabaseCount('content_handoff_records', 0);
        $this->assertSame(ContentVersionStatus::APPROVED, ContentVersion::query()->findOrFail($version->id)->status);
    }

    public function test_handoff_is_blocked_when_media_is_missing_even_if_an_approval_record_exists(): void
    {
        $version = $this->createVersion(['media_reference' => null]);
        $this->addApproval($version);
        ContentVersion::query()->findOrFail($version->id)->update(['status' => ContentVersionStatus::APPROVED]);

        try {
            app(SimulatedContentHandoffService::class)->handoff($version->id);
            $this->fail('Handoff must require a media reference.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('media_reference', $exception->errors());
        }

        $this->assertDatabaseCount('content_handoff_records', 0);
    }

    public function test_handoff_is_blocked_when_work_allocation_is_removed_after_approval(): void
    {
        $version = $this->createApprovedVersion();
        $persisted = ContentVersion::query()->findOrFail($version->id);
        $persisted->briefing->contentIdea->workAllocations()->delete();

        try {
            app(SimulatedContentHandoffService::class)->handoff($version->id);
            $this->fail('Handoff must require a current work allocation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('work_allocations', $exception->errors());
        }

        $this->assertDatabaseCount('content_handoff_records', 0);
        $this->assertSame(ContentVersionStatus::APPROVED, ContentVersion::query()->findOrFail($version->id)->status);
    }

    public function test_non_approved_versions_cannot_be_handed_off(): void
    {
        $version = $this->createVersion();

        $this->expectException(LogicException::class);
        app(SimulatedContentHandoffService::class)->handoff($version->id);
    }

    private function createApprovedVersion(): ContentVersionRecord
    {
        $version = $this->createVersion();
        $service = app(ContentApprovalService::class);
        $review = $service->submitForReview($version->id);

        return $service->approve($review->id, 'Person B');
    }

    private function createVersion(array $overrides = []): ContentVersionRecord
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
        $idea->workAllocations()->create([
            'person_key' => 'person_a',
            'work_type' => WorkAllocationType::PRODUCTION,
            'estimated_minutes' => 30,
        ]);
        $briefing = $idea->briefing()->create([
            'hook' => 'Your glasses fog up in winter?',
            'body' => 'A friendly explanation.',
            'filming_plan' => 'Film a close-up demonstration.',
            'on_screen_text' => ['Winter eyewear tips'],
            'cover_idea' => 'Close-up of glasses.',
            'cta_text' => 'Book a consultation.',
            'appointment_url' => 'https://example.com/appointments',
            'status' => ContentBriefingStatus::DRAFT,
        ]);

        return app(ContentVersionCreator::class)->create((int) $briefing->getKey(), array_merge([
            'hook' => 'Try these winter eyewear tips.',
            'body' => 'Keep your glasses clear with these simple habits.',
            'channel_variants' => [
                'instagram' => ['text' => 'Save these three winter eyewear tips.'],
                'tiktok' => ['text' => 'Three quick tips to keep your glasses clear.'],
            ],
            'media_reference' => 'synthetic-media/winter-eyewear-demo.mp4',
            'channels' => ['instagram', 'tiktok'],
            'scheduled_at' => '2026-11-25',
        ], $overrides));
    }

    private function addApproval(ContentVersionRecord $version): void
    {
        ContentVersionApproval::create([
            'content_version_id' => $version->id,
            'reviewer' => 'Person B',
            'decision' => ContentApprovalDecision::APPROVED,
            'content_hash' => $version->contentHash,
            'comment' => 'Approved for handoff.',
            'reviewed_at' => Carbon::now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Services\ContentApprovalService;
use App\Application\Services\ContentVersionCreator;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Domain\Shared\Enums\ContentBriefingStatus;
use App\Domain\Shared\Enums\ContentIdeaPriority;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Domain\Shared\Enums\WorkAllocationType;
use App\Infrastructure\Persistence\Eloquent\Models\ContentIdea;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersionApproval;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class ContentApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_moves_from_draft_to_review_and_approved_with_hash_bound_approval(): void
    {
        [$version] = $this->createVersion();
        $service = app(ContentApprovalService::class);

        $review = $service->submitForReview($version->id);
        $this->assertSame(ContentVersionStatus::IN_REVIEW, $review->status);

        $approved = $service->approve($review->id, 'Person B', 'Looks good.');
        $this->assertSame(ContentVersionStatus::APPROVED, $approved->status);

        $approval = ContentVersionApproval::query()->sole();
        $this->assertSame(ContentApprovalDecision::APPROVED, $approval->decision);
        $this->assertSame('Person B', $approval->reviewer);
        $this->assertSame($version->contentHash, $approval->content_hash);
        $this->assertSame('Looks good.', $approval->comment);
    }

    public function test_approval_is_blocked_when_media_reference_is_missing(): void
    {
        [$version] = $this->createVersion(['media_reference' => null]);
        $service = app(ContentApprovalService::class);
        $review = $service->submitForReview($version->id);

        try {
            $service->approve($review->id, 'Person B');
            $this->fail('Approval should require a media reference.');
        } catch (ValidationException $validationException) {
            $this->assertArrayHasKey('media_reference', $validationException->errors());
        }

        $this->assertSame(ContentVersionStatus::IN_REVIEW, ContentVersion::query()->findOrFail($version->id)->status);
        $this->assertDatabaseCount('content_version_approvals', 0);
    }

    public function test_rejected_version_is_terminal_and_keeps_rejection_record(): void
    {
        [$version] = $this->createVersion();
        $service = app(ContentApprovalService::class);
        $review = $service->submitForReview($version->id);
        $rejected = $service->reject($review->id, 'Person B', 'Please revise the wording.');

        $this->assertSame(ContentVersionStatus::REJECTED, $rejected->status);
        $this->assertSame(ContentApprovalDecision::REJECTED, ContentVersionApproval::query()->sole()->decision);

        $this->expectException(LogicException::class);
        $service->submitForReview($rejected->id);
    }

    public function test_only_draft_versions_can_be_submitted_for_review(): void
    {
        [$version] = $this->createVersion();
        $service = app(ContentApprovalService::class);
        $review = $service->submitForReview($version->id);

        $this->expectException(LogicException::class);
        $service->submitForReview($review->id);
    }

    public function test_approval_requires_a_work_allocation_and_responsible_person(): void
    {
        [$version, $idea] = $this->createVersion(withAllocation: false);
        $idea->update(['responsible_person' => '']);
        $service = app(ContentApprovalService::class);
        $review = $service->submitForReview($version->id);

        try {
            $service->approve($review->id, 'Person B');
            $this->fail('Approval should require assignment and allocation.');
        } catch (ValidationException $validationException) {
            $this->assertArrayHasKey('responsible_person', $validationException->errors());
            $this->assertArrayHasKey('work_allocations', $validationException->errors());
        }
    }

    /** @return array{0: ContentVersionRecord, 1: ContentIdea} */
    private function createVersion(array $overrides = [], bool $withAllocation = true): array
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

        if ($withAllocation) {
            $idea->workAllocations()->create([
                'person_key' => 'person_a',
                'work_type' => WorkAllocationType::PRODUCTION,
                'estimated_minutes' => 30,
            ]);
        }

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
        $data = array_merge([
            'hook' => 'Try these winter eyewear tips.',
            'body' => 'Keep your glasses clear with these simple habits.',
            'channel_variants' => [
                'instagram' => ['text' => 'Save these three winter eyewear tips.'],
                'tiktok' => ['text' => 'Three quick tips to keep your glasses clear.'],
            ],
            'media_reference' => 'synthetic-media/winter-eyewear-demo.mp4',
            'channels' => ['instagram', 'tiktok'],
            'scheduled_at' => '2026-11-25',
        ], $overrides);

        $version = app(ContentVersionCreator::class)->create((int) $briefing->getKey(), $data);

        return [$version, $idea];
    }
}

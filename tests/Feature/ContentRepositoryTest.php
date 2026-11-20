<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Domain\Shared\Enums\ContentBriefingStatus;
use App\Domain\Shared\Enums\ContentIdeaPriority;
use App\Domain\Shared\Enums\WorkAllocationType;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;
use App\Domain\Repositories\ContentHandoffRepositoryInterface;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentApprovalRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentHandoffRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentVersionRepository;
use App\Application\Services\ContentVersionCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_contracts_are_bound_to_eloquent_implementations(): void
    {
        $this->assertInstanceOf(
            EloquentContentVersionRepository::class,
            app(ContentVersionRepositoryInterface::class),
        );
        $this->assertInstanceOf(
            EloquentContentApprovalRepository::class,
            app(ContentApprovalRepositoryInterface::class),
        );
        $this->assertInstanceOf(
            EloquentContentHandoffRepository::class,
            app(ContentHandoffRepositoryInterface::class),
        );
    }

    public function test_version_repository_locks_a_version_and_loads_readiness_relations(): void
    {
        $version = $this->createVersion();

        $lockedVersion = app(ContentVersionRepositoryInterface::class)
            ->lockForWorkflow($version->id, withReadinessRelations: true);

        $this->assertSame($version->id, $lockedVersion->id);
        $this->assertSame('person_a', $lockedVersion->responsiblePerson);
        $this->assertSame(1, $lockedVersion->workAllocationCount);
    }

    public function test_approval_repository_matches_the_exact_version_hash_and_decision(): void
    {
        $version = $this->createVersion();
        $repository = app(ContentApprovalRepositoryInterface::class);

        $first = $repository->create([
            'content_version_id' => $version->id,
            'reviewer' => 'Reviewer A',
            'decision' => ContentApprovalDecision::APPROVED,
            'content_hash' => $version->contentHash,
            'comment' => 'First approval.',
            'reviewed_at' => Carbon::parse('2026-11-20 10:00:00'),
        ]);

        $repository->create([
            'content_version_id' => $version->id,
            'reviewer' => 'Reviewer B',
            'decision' => ContentApprovalDecision::APPROVED,
            'content_hash' => str_repeat('a', 64),
            'comment' => 'Approval for another hash.',
            'reviewed_at' => Carbon::parse('2026-11-20 11:00:00'),
        ]);

        $latestMatching = $repository->findLatestForVersionAndHash(
            $version->id,
            ContentApprovalDecision::APPROVED,
            $version->contentHash,
        );

        $this->assertNotNull($latestMatching);
        $this->assertSame($first->id, $latestMatching->id);
        $this->assertNull($repository->findLatestForVersionAndHash(
            $version->id,
            ContentApprovalDecision::REJECTED,
            $version->contentHash,
        ));
    }

    private function createVersion(): ContentVersionRecord
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

        return app(ContentVersionCreator::class)->create((int) $briefing->getKey(), [
            'hook' => 'Try these winter eyewear tips.',
            'body' => 'Keep your glasses clear with these simple habits.',
            'channel_variants' => [
                'instagram' => ['text' => 'Save these three winter eyewear tips.'],
                'tiktok' => ['text' => 'Three quick tips to keep your glasses clear.'],
            ],
            'media_reference' => 'synthetic-media/winter-eyewear-demo.mp4',
            'channels' => ['instagram', 'tiktok'],
            'scheduled_at' => '2026-11-25',
        ]);
    }
}

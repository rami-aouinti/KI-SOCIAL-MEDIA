<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shared\Enums\WorkAllocationType;
use App\Infrastructure\Persistence\Eloquent\Models\ContentIdea;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use App\Infrastructure\Persistence\Eloquent\Models\WorkAllocation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkAllocationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_allocation_is_persisted_with_typed_work_type(): void
    {
        $idea = $this->createContentIdea();

        $allocation = $idea->workAllocations()->create([
            'person_key' => 'person_a',
            'work_type' => WorkAllocationType::PRODUCTION,
            'estimated_minutes' => 25,
        ]);

        $this->assertDatabaseHas('work_allocations', [
            'id' => $allocation->id,
            'content_idea_id' => $idea->id,
            'person_key' => 'person_a',
            'work_type' => 'production',
            'estimated_minutes' => 25,
        ]);

        $this->assertSame(WorkAllocationType::PRODUCTION, $allocation->work_type);
        $this->assertSame(25, $allocation->estimated_minutes);
    }

    public function test_allocation_belongs_to_content_idea(): void
    {
        $idea = $this->createContentIdea();

        $allocation = WorkAllocation::create([
            'content_idea_id' => $idea->id,
            'person_key' => 'person_b',
            'work_type' => WorkAllocationType::APPROVAL,
            'estimated_minutes' => 10,
        ]);

        $this->assertTrue($allocation->contentIdea->is($idea));
    }

    public function test_duplicate_allocation_for_same_person_and_work_type_is_rejected(): void
    {
        $idea = $this->createContentIdea();

        $attributes = [
            'person_key' => 'person_a',
            'work_type' => WorkAllocationType::PRODUCTION,
            'estimated_minutes' => 20,
        ];

        $idea->workAllocations()->create($attributes);

        $this->expectException(QueryException::class);

        $idea->workAllocations()->create($attributes);
    }

    public function test_deleting_content_idea_deletes_its_allocations(): void
    {
        $idea = $this->createContentIdea();

        $allocation = $idea->workAllocations()->create([
            'person_key' => 'person_a',
            'work_type' => WorkAllocationType::COORDINATION,
            'estimated_minutes' => 5,
        ]);

        $allocationId = $allocation->id;
        $idea->delete();

        $this->assertDatabaseMissing('work_allocations', [
            'id' => $allocationId,
        ]);
    }

    private function createContentIdea(): ContentIdea
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'input_data' => [],
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
        ]);

        return $plan->contentIdeas()->create([
            'title' => 'Winter eyewear',
            'topic' => 'Present winter glasses collection.',
            'target_audience' => 'Young adults',
            'channels' => ['instagram', 'tiktok'],
            'format' => 'reel',
            'priority_type' => 'priority',
            'responsible_person' => 'person_a',
            'planned_at' => '2026-11-25',
            'estimated_minutes' => 40,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Services\CapacityPlanner;
use App\Domain\Shared\Enums\WorkAllocationType;
use App\Infrastructure\Persistence\Eloquent\Models\ContentIdea;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CapacityPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_allocations_per_person_and_reports_remaining_capacity(): void
    {
        $plan = $this->createPlan();

        $idea = $this->createIdea($plan, 'Instagram reel');
        $idea->workAllocations()->createMany([
            [
                'person_key' => 'person_a',
                'work_type' => WorkAllocationType::PRODUCTION,
                'estimated_minutes' => 30,
            ],
            [
                'person_key' => 'person_a',
                'work_type' => WorkAllocationType::COORDINATION,
                'estimated_minutes' => 10,
            ],
            [
                'person_key' => 'person_b',
                'work_type' => WorkAllocationType::APPROVAL,
                'estimated_minutes' => 15,
            ],
        ]);

        $report = app(CapacityPlanner::class)->analyze($plan);

        $this->assertTrue($report['within_capacity']);
        $this->assertSame(40, $report['people']['person_a']['allocated_minutes']);
        $this->assertSame(90, $report['people']['person_a']['capacity_minutes']);
        $this->assertSame(50, $report['people']['person_a']['remaining_minutes']);
        $this->assertSame(15, $report['people']['person_b']['allocated_minutes']);
        $this->assertSame(135, $report['people']['person_b']['remaining_minutes']);
        $this->assertSame([], $report['warnings']);
    }

    public function test_it_reuses_eager_loaded_allocations_without_additional_queries(): void
    {
        $plan = $this->createPlan();
        $idea = $this->createIdea($plan, 'Preloaded campaign');
        $idea->workAllocations()->create([
            'person_key' => 'person_a',
            'work_type' => WorkAllocationType::PRODUCTION,
            'estimated_minutes' => 25,
        ]);

        $plan->load('contentIdeas.workAllocations');

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        $report = app(CapacityPlanner::class)->analyze($plan);

        $this->assertSame(25, $report['people']['person_a']['allocated_minutes']);
        $this->assertSame(0, $queries);
    }

    public function test_it_warns_when_a_person_exceeds_the_weekly_capacity(): void
    {
        $plan = $this->createPlan();
        $idea = $this->createIdea($plan, 'Campaign video');

        $idea->workAllocations()->create([
            'person_key' => 'person_a',
            'work_type' => WorkAllocationType::PRODUCTION,
            'estimated_minutes' => 100,
        ]);

        $report = app(CapacityPlanner::class)->analyze($plan);

        $this->assertFalse($report['within_capacity']);
        $this->assertTrue($report['people']['person_a']['is_over_capacity']);
        $this->assertSame(10, $report['people']['person_a']['exceeded_by_minutes']);
        $this->assertSame('capacity_exceeded', $report['warnings'][0]['type']);
        $this->assertStringContainsString('10 minutes', $report['warnings'][0]['message']);
    }

    public function test_it_reports_people_with_no_allocations_as_zero_workload(): void
    {
        $plan = $this->createPlan();

        $report = app(CapacityPlanner::class)->analyze($plan);

        $this->assertTrue($report['within_capacity']);
        $this->assertSame(0, $report['people']['person_a']['allocated_minutes']);
        $this->assertSame(90, $report['people']['person_a']['capacity_minutes']);
        $this->assertSame(0, $report['people']['person_b']['allocated_minutes']);
        $this->assertSame(150, $report['people']['person_b']['capacity_minutes']);
    }

    public function test_it_warns_when_an_allocation_has_no_configured_capacity(): void
    {
        $plan = $this->createPlan();
        $idea = $this->createIdea($plan, 'Unassigned campaign');

        $idea->workAllocations()->create([
            'person_key' => 'person_c',
            'work_type' => WorkAllocationType::PRODUCTION,
            'estimated_minutes' => 20,
        ]);

        $report = app(CapacityPlanner::class)->analyze($plan);

        $this->assertFalse($report['within_capacity']);
        $this->assertNull($report['people']['person_c']['capacity_minutes']);
        $this->assertSame('capacity_missing', $report['warnings'][0]['type']);
    }

    private function createPlan(): WeeklyPlan
    {
        return WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'input_data' => [],
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
        ]);
    }

    private function createIdea(WeeklyPlan $plan, string $title): ContentIdea
    {
        return $plan->contentIdeas()->create([
            'title' => $title,
            'topic' => 'Create useful optical content.',
            'target_audience' => 'Adults',
            'channels' => ['instagram'],
            'format' => 'reel',
            'priority_type' => 'priority',
            'responsible_person' => 'person_a',
            'planned_at' => '2026-11-25',
            'estimated_minutes' => 30,
        ]);
    }
}

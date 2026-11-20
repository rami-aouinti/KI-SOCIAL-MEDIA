<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Services\CapacityPlanner;
use Tests\TestCase;

class CapacityPlannerTest extends TestCase
{
    public function test_it_sums_allocations_per_person_and_reports_remaining_capacity(): void
    {
        $report = app(CapacityPlanner::class)->analyze(
            ['person_a' => 90, 'person_b' => 150],
            [
                ['person_key' => 'person_a', 'estimated_minutes' => 30],
                ['person_key' => 'person_a', 'estimated_minutes' => 10],
                ['person_key' => 'person_b', 'estimated_minutes' => 15],
            ],
        );

        $this->assertTrue($report['within_capacity']);
        $this->assertSame(40, $report['people']['person_a']['allocated_minutes']);
        $this->assertSame(90, $report['people']['person_a']['capacity_minutes']);
        $this->assertSame(50, $report['people']['person_a']['remaining_minutes']);
        $this->assertSame(15, $report['people']['person_b']['allocated_minutes']);
        $this->assertSame(135, $report['people']['person_b']['remaining_minutes']);
        $this->assertSame([], $report['warnings']);
    }

    public function test_it_reports_configured_people_with_no_allocations_as_zero_workload(): void
    {
        $report = app(CapacityPlanner::class)->analyze(
            ['person_a' => 90, 'person_b' => 150],
            [],
        );

        $this->assertTrue($report['within_capacity']);
        $this->assertSame(0, $report['people']['person_a']['allocated_minutes']);
        $this->assertSame(90, $report['people']['person_a']['capacity_minutes']);
        $this->assertSame(0, $report['people']['person_b']['allocated_minutes']);
        $this->assertSame(150, $report['people']['person_b']['capacity_minutes']);
    }

    public function test_it_warns_when_a_person_exceeds_the_weekly_capacity(): void
    {
        $report = app(CapacityPlanner::class)->analyze(
            ['person_a' => 90],
            [['person_key' => 'person_a', 'estimated_minutes' => 100]],
        );

        $this->assertFalse($report['within_capacity']);
        $this->assertTrue($report['people']['person_a']['is_over_capacity']);
        $this->assertSame(10, $report['people']['person_a']['exceeded_by_minutes']);
        $this->assertSame('capacity_exceeded', $report['warnings'][0]['type']);
        $this->assertStringContainsString('10 minutes', $report['warnings'][0]['message']);
    }

    public function test_it_warns_when_an_allocation_has_no_configured_capacity(): void
    {
        $report = app(CapacityPlanner::class)->analyze(
            ['person_a' => 90],
            [['person_key' => 'person_c', 'estimated_minutes' => 20]],
        );

        $this->assertFalse($report['within_capacity']);
        $this->assertNull($report['people']['person_c']['capacity_minutes']);
        $this->assertSame('capacity_missing', $report['warnings'][0]['type']);
    }
}

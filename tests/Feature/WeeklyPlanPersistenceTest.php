<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shared\Enums\WeeklyPlanStatus;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyPlanPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_plan_is_persisted_with_default_status(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'input_data' => [
                'topics' => ['Winter collection'],
            ],
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
        ]);

        $this->assertDatabaseHas('weekly_plans', [
            'id' => $plan->id,
            'week_start' => '2026-11-23',
            'status' => 'draft',
        ]);

        $this->assertSame(WeeklyPlanStatus::DRAFT, $plan->status);
    }

    public function test_json_attributes_are_restored_after_database_read(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-30',
            'input_data' => [
                'topics' => ['Eye health'],
            ],
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
        ]);

        $reloadedPlan = WeeklyPlan::findOrFail($plan->id);

        $this->assertSame(
            ['topics' => ['Eye health']],
            $reloadedPlan->input_data
        );

        $this->assertSame(
            ['person_a' => 90, 'person_b' => 150],
            $reloadedPlan->capacity_limits
        );
    }

    public function test_week_start_must_be_unique(): void
    {
        WeeklyPlan::create([
            'week_start' => '2026-12-07',
            'input_data' => [],
            'capacity_limits' => [],
        ]);

        $this->expectException(QueryException::class);

        WeeklyPlan::create([
            'week_start' => '2026-12-07',
            'input_data' => [],
            'capacity_limits' => [],
        ]);
    }
}

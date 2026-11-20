<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\WeeklyPlanStatus;
use App\Models\WeeklyPlan;
use PHPUnit\Framework\TestCase;

class WeeklyPlanTest extends TestCase
{
    public function test_weekly_plan_has_correct_default_status(): void
    {
        $plan = new WeeklyPlan();

        $this->assertSame(
            WeeklyPlanStatus::DRAFT,
            $plan->status
        );
    }

    public function test_weekly_plan_casts_json_attributes_to_arrays(): void
    {
        $plan = new WeeklyPlan([
            'input_data' => ['topics' => ['Winter collection']],
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
        ]);

        $this->assertSame(
            ['topics' => ['Winter collection']],
            $plan->input_data
        );

        $this->assertSame(
            ['person_a' => 90, 'person_b' => 150],
            $plan->capacity_limits
        );
    }
}

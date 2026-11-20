<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\InspirationReference;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspirationReferencePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspiration_is_persisted_and_belongs_to_weekly_plan(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'input_data' => [],
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
        ]);

        $inspiration = $plan->inspirations()->create([
            'channel' => 'instagram',
            'source_id' => 'demo-source-001',
            'description' => 'Short videos presenting winter eyewear.',
            'metrics' => [
                'views' => 12000,
                'engagement_rate' => 4.5,
            ],
        ]);

        $this->assertDatabaseHas('inspiration_references', [
            'id' => $inspiration->id,
            'weekly_plan_id' => $plan->id,
            'source_id' => 'demo-source-001',
        ]);

        $this->assertTrue($inspiration->weeklyPlan->is($plan));
        $this->assertCount(1, $plan->inspirations);
    }

    public function test_metrics_are_restored_as_array(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-30',
            'input_data' => [],
            'capacity_limits' => [],
        ]);

        $inspiration = $plan->inspirations()->create([
            'channel' => 'tiktok',
            'source_id' => 'demo-source-002',
            'description' => 'A short educational video.',
            'metrics' => [
                'views' => 5000,
            ],
        ]);

        $reloaded = InspirationReference::findOrFail($inspiration->id);

        $this->assertSame(
            ['views' => 5000],
            $reloaded->metrics
        );
    }

    public function test_same_source_cannot_be_added_twice_to_same_plan(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-12-07',
            'input_data' => [],
            'capacity_limits' => [],
        ]);

        $data = [
            'channel' => 'instagram',
            'source_id' => 'demo-source-003',
            'description' => 'Example inspiration.',
        ];

        $plan->inspirations()->create($data);

        $this->expectException(QueryException::class);

        $plan->inspirations()->create($data);
    }

    public function test_deleting_weekly_plan_deletes_its_inspirations(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-12-14',
            'input_data' => [],
            'capacity_limits' => [],
        ]);

        $inspiration = $plan->inspirations()->create([
            'channel' => 'linkedin',
            'source_id' => 'demo-source-004',
            'description' => 'Professional eyewear content.',
        ]);

        $inspirationId = $inspiration->id;

        $plan->delete();

        $this->assertDatabaseMissing('inspiration_references', [
            'id' => $inspirationId,
        ]);
    }
}

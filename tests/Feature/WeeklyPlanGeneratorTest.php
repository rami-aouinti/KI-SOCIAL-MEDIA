<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Services\WeeklyPlanGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WeeklyPlanGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_exactly_three_priorities_two_reserves_and_inspirations(): void
    {
        $result = app(WeeklyPlanGenerator::class)->generate($this->validInput());
        $plan = $result['plan'];

        $this->assertDatabaseCount('weekly_plans', 1);
        $this->assertDatabaseCount('inspiration_references', 3);
        $this->assertDatabaseCount('content_ideas', 5);
        $this->assertDatabaseCount('work_allocations', 5);

        $this->assertSame(3, $plan->contentIdeas->where('priority_type.value', 'priority')->count());
        $this->assertSame(2, $plan->contentIdeas->where('priority_type.value', 'reserve')->count());
        $this->assertCount(5, $result['selection_reasons']);
        $this->assertTrue($result['capacity_report']['within_capacity']);
        $this->assertSame(50, $result['capacity_report']['people']['person_a']['allocated_minutes']);
    }

    public function test_it_rejects_a_plan_without_exactly_three_priorities_and_two_reserves(): void
    {
        $input = $this->validInput();
        $input['ideas'][2]['priority_type'] = 'reserve';

        try {
            app(WeeklyPlanGenerator::class)->generate($input);
            $this->fail('Expected validation to reject an invalid priority/reserve split.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ideas', $exception->errors());
        }

        $this->assertDatabaseCount('weekly_plans', 0);
    }

    public function test_it_requires_at_least_three_inspiration_references(): void
    {
        $input = $this->validInput();
        $input['inspirations'] = array_slice($input['inspirations'], 0, 2);

        $this->expectException(ValidationException::class);

        app(WeeklyPlanGenerator::class)->generate($input);
    }

    public function test_it_keeps_the_plan_and_returns_a_warning_when_capacity_is_exceeded(): void
    {
        $input = $this->validInput();
        $input['ideas'][0]['allocations'][0]['estimated_minutes'] = 100;

        $result = app(WeeklyPlanGenerator::class)->generate($input);

        $this->assertDatabaseCount('weekly_plans', 1);
        $this->assertFalse($result['capacity_report']['within_capacity']);
        $this->assertSame(50, $result['capacity_report']['people']['person_a']['exceeded_by_minutes']);
        $this->assertSame('capacity_exceeded', $result['capacity_report']['warnings'][0]['type']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validInput(): array
    {
        return [
            'week_start' => '2026-11-23',
            'seasonal_occasion' => 'Winter eyewear',
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
            'inspirations' => [
                [
                    'channel' => 'instagram',
                    'source_id' => 'demo-insta-001',
                    'description' => 'Simulated example about winter eyewear.',
                    'metrics' => null,
                ],
                [
                    'channel' => 'tiktok',
                    'source_id' => 'demo-tiktok-002',
                    'description' => 'Simulated short-video format reference.',
                    'metrics' => ['views' => null],
                ],
                [
                    'channel' => 'facebook',
                    'source_id' => 'demo-facebook-003',
                    'description' => 'Simulated community content reference.',
                ],
            ],
            'ideas' => [
                $this->idea('Winter eye-care tips', 'priority', 'person_a'),
                $this->idea('Choosing sunglasses', 'priority', 'person_a'),
                $this->idea('Behind the scenes', 'priority', 'person_a'),
                $this->idea('Frame care checklist', 'reserve', 'person_a'),
                $this->idea('Meet the team', 'reserve', 'person_a'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function idea(string $title, string $priorityType, string $person): array
    {
        return [
            'title' => $title,
            'topic' => 'Helpful and accessible optical information.',
            'target_audience' => 'Adults interested in eyewear',
            'channels' => ['instagram'],
            'format' => 'reel',
            'priority_type' => $priorityType,
            'selection_reason' => 'Relevant to the audience and feasible within the weekly plan.',
            'responsible_person' => $person,
            'planned_at' => '2026-11-25',
            'allocations' => [
                [
                    'person_key' => $person,
                    'work_type' => 'production',
                    'estimated_minutes' => 10,
                ],
            ],
        ];
    }
}

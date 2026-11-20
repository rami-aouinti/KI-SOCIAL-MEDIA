<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentIdeaPriority;
use App\Enums\ContentIdeaStatus;
use App\Models\ContentIdea;
use App\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentIdeaPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_idea_is_persisted_with_default_status(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'input_data' => [],
            'capacity_limits' => [
                'person_a' => 90,
                'person_b' => 150,
            ],
        ]);

        $idea = $plan->contentIdeas()->create([
            'title' => 'Winter eyewear',
            'topic' => 'Present winter glasses collection.',
            'target_audience' => 'Young adults',
            'channels' => ['instagram', 'tiktok'],
            'format' => 'reel',
            'priority_type' => ContentIdeaPriority::PRIORITY,
            'responsible_person' => 'person_a',
            'planned_at' => '2026-11-25',
            'estimated_minutes' => 35,
        ]);

        $this->assertDatabaseHas('content_ideas', [
            'id' => $idea->id,
            'weekly_plan_id' => $plan->id,
            'title' => 'Winter eyewear',
            'status' => 'planned',
        ]);

        $this->assertSame(ContentIdeaStatus::PLANNED, $idea->status);
        $this->assertSame(ContentIdeaPriority::PRIORITY, $idea->priority_type);
    }

    public function test_channels_are_restored_as_array(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-30',
            'input_data' => [],
            'capacity_limits' => [],
        ]);

        $idea = $plan->contentIdeas()->create([
            'title' => 'Eye health tips',
            'topic' => 'Share useful eye care advice.',
            'target_audience' => 'Families',
            'channels' => ['instagram', 'facebook'],
            'format' => 'carousel',
            'priority_type' => ContentIdeaPriority::RESERVE,
            'responsible_person' => 'person_b',
            'planned_at' => '2026-12-02',
            'estimated_minutes' => 40,
        ]);

        $reloaded = ContentIdea::findOrFail($idea->id);

        $this->assertSame(
            ['instagram', 'facebook'],
            $reloaded->channels
        );
    }

    public function test_content_idea_belongs_to_weekly_plan(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-12-07',
            'input_data' => [],
            'capacity_limits' => [],
        ]);

        $idea = $plan->contentIdeas()->create([
            'title' => 'New collection',
            'topic' => 'Show new glasses.',
            'target_audience' => 'Adults',
            'channels' => ['linkedin'],
            'format' => 'post',
            'priority_type' => ContentIdeaPriority::PRIORITY,
            'responsible_person' => 'person_a',
            'planned_at' => '2026-12-09',
            'estimated_minutes' => 25,
        ]);

        $this->assertTrue($idea->weeklyPlan->is($plan));
    }

    public function test_deleting_weekly_plan_deletes_content_ideas(): void
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-12-14',
            'input_data' => [],
            'capacity_limits' => [],
        ]);

        $idea = $plan->contentIdeas()->create([
            'title' => 'Eye care',
            'topic' => 'Eye care tips.',
            'target_audience' => 'Everyone',
            'channels' => ['instagram'],
            'format' => 'story',
            'priority_type' => ContentIdeaPriority::RESERVE,
            'responsible_person' => 'person_b',
            'planned_at' => '2026-12-16',
            'estimated_minutes' => 20,
        ]);

        $ideaId = $idea->id;

        $plan->delete();

        $this->assertDatabaseMissing('content_ideas', [
            'id' => $ideaId,
        ]);
    }
}

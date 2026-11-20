<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentBriefingStatus;
use App\Enums\ContentIdeaPriority;
use App\Models\ContentBriefing;
use App\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentBriefingPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_briefing_is_persisted_with_default_draft_status(): void
    {
        $idea = $this->createPriorityIdea();

        $briefing = $idea->briefing()->create($this->briefingData());

        $this->assertDatabaseHas('content_briefings', [
            'id' => $briefing->id,
            'content_idea_id' => $idea->id,
            'status' => 'draft',
        ]);
        $this->assertSame(ContentBriefingStatus::DRAFT, $briefing->status);
        $this->assertSame(
            ['0:00-0:03 Hook', '0:03-0:10 Demonstration'],
            $briefing->on_screen_text
        );
    }

    public function test_briefing_belongs_to_content_idea(): void
    {
        $idea = $this->createPriorityIdea();
        $briefing = $idea->briefing()->create($this->briefingData());

        $this->assertTrue($briefing->contentIdea->is($idea));
    }

    public function test_only_one_briefing_can_exist_per_content_idea(): void
    {
        $idea = $this->createPriorityIdea();
        $idea->briefing()->create($this->briefingData());

        $this->expectException(\Illuminate\Database\QueryException::class);

        $idea->briefing()->create($this->briefingData());
    }

    public function test_deleting_content_idea_deletes_its_briefing(): void
    {
        $idea = $this->createPriorityIdea();
        $briefing = $idea->briefing()->create($this->briefingData());
        $briefingId = $briefing->id;

        $idea->delete();

        $this->assertDatabaseMissing('content_briefings', [
            'id' => $briefingId,
        ]);
    }

    private function createPriorityIdea(): \App\Models\ContentIdea
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'input_data' => [],
            'capacity_limits' => ['person_a' => 90, 'person_b' => 150],
        ]);

        return $plan->contentIdeas()->create([
            'title' => 'Winter eyewear tips',
            'topic' => 'Share practical winter eyewear advice.',
            'target_audience' => 'Adults interested in eyewear',
            'channels' => ['instagram', 'tiktok'],
            'format' => 'reel',
            'priority_type' => ContentIdeaPriority::PRIORITY,
            'selection_reason' => 'Relevant seasonal topic.',
            'responsible_person' => 'person_a',
            'planned_at' => '2026-11-25',
            'estimated_minutes' => 40,
        ]);
    }

    /** @return array<string, mixed> */
    private function briefingData(): array
    {
        return [
            'hook' => 'Your glasses fog up in winter? Try this.',
            'body' => 'A friendly, practical explanation for the audience.',
            'filming_plan' => 'Film a close-up, then demonstrate the care tip.',
            'on_screen_text' => [
                '0:00-0:03 Hook',
                '0:03-0:10 Demonstration',
            ],
            'cover_idea' => 'Close-up of glasses with a short readable title.',
            'cta_text' => 'Book a free consultation using our demo link.',
            'appointment_url' => 'https://example.com/appointments',
        ];
    }
}

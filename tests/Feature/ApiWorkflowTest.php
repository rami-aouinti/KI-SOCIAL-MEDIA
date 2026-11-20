<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\ContentIdea;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_generation_endpoint_validates_input_and_persists_a_plan(): void
    {
        $token = $this->createToken(['plans:write']);

        $this->withToken($token)
            ->postJson('/api/v1/weekly-plans', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['week_start', 'capacity_limits', 'inspirations', 'ideas']);

        $this->assertDatabaseCount('weekly_plans', 0);
    }

    public function test_plan_generation_endpoint_persists_a_validated_plan(): void
    {
        $token = $this->createToken(['plans:write']);

        $this->withToken($token)
            ->postJson('/api/v1/weekly-plans', $this->validPlanInput())
            ->assertCreated()
            ->assertJsonPath('data.week_start', '2026-11-23')
            ->assertJsonCount(5, 'data.content_ideas')
            ->assertJsonPath('capacity_report.within_capacity', true);

        $this->assertDatabaseCount('weekly_plans', 1);
        $this->assertDatabaseCount('content_ideas', 5);
    }

    public function test_content_idea_endpoint_returns_the_idea_and_its_workflow(): void
    {
        $this->seed(DemoContentSeeder::class);
        $idea = ContentIdea::query()->whereHas('briefing')->firstOrFail();
        $token = $this->createToken(['content:read']);

        $this->withToken($token)
            ->getJson('/api/v1/content-ideas/'.$idea->getKey())
            ->assertOk()
            ->assertJsonPath('data.id', $idea->getKey())
            ->assertJsonPath('data.title', $idea->title)
            ->assertJsonStructure(['data' => ['work_allocations', 'briefing']]);
    }

    public function test_version_creation_and_review_submission_are_available_through_the_api(): void
    {
        $this->seed(DemoContentSeeder::class);
        $idea = ContentIdea::query()->with('briefing')->whereHas('briefing')->firstOrFail();
        $token = $this->createToken(['content:write', 'content:read']);

        $this->withToken($token)
            ->postJson('/api/v1/content-briefings/'.$idea->briefing->getKey().'/versions', [
                'hook' => 'A practical winter eye-care tip',
                'body' => 'Protect your eyes from winter glare and changing light.',
                'channel_variants' => [
                    'instagram' => ['text' => 'Protect your eyes this winter.'],
                ],
                'channels' => ['instagram'],
                'scheduled_at' => '2026-11-26',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version_number', 2);

        $versionId = \App\Infrastructure\Persistence\Eloquent\Models\ContentVersion::query()
            ->where('content_briefing_id', $idea->briefing->getKey())
            ->where('version_number', 2)
            ->value('id');

        $this->withToken($token)
            ->postJson('/api/v1/content-versions/'.$versionId.'/submit-review')
            ->assertOk()
            ->assertJsonPath('data.status', 'in_review');
    }

    /**
     * @param list<string> $abilities
     */
    private function createToken(array $abilities): string
    {
        User::factory()->create()->apiTokens()->create([
            'name' => 'API workflow test',
            'token_hash' => hash('sha256', $plainText = 'ksm_test_'.str()->random(48)),
            'abilities' => $abilities,
            'expires_at' => now()->addDays(30),
        ]);

        return $plainText;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPlanInput(): array
    {
        return [
            'week_start' => '2026-11-23',
            'seasonal_occasion' => 'Winter eyewear',
            'capacity_limits' => ['person_a' => 90, 'person_b' => 150],
            'inspirations' => [
                ['channel' => 'instagram', 'source_id' => 'api-insta-001', 'description' => 'Winter eyewear inspiration'],
                ['channel' => 'tiktok', 'source_id' => 'api-tiktok-002', 'description' => 'Short video inspiration'],
                ['channel' => 'facebook', 'source_id' => 'api-facebook-003', 'description' => 'Community inspiration'],
            ],
            'ideas' => [
                $this->idea('Winter eye-care tips', 'priority'),
                $this->idea('Choosing sunglasses', 'priority'),
                $this->idea('Behind the scenes', 'priority'),
                $this->idea('Frame care checklist', 'reserve'),
                $this->idea('Meet the team', 'reserve'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function idea(string $title, string $priorityType): array
    {
        return [
            'title' => $title,
            'topic' => 'Helpful optical information for the winter season.',
            'target_audience' => 'Adults interested in eyewear',
            'channels' => ['instagram'],
            'format' => 'reel',
            'priority_type' => $priorityType,
            'selection_reason' => 'Relevant to the audience and feasible within the weekly plan.',
            'responsible_person' => 'person_a',
            'planned_at' => '2026-11-25',
            'allocations' => [
                ['person_key' => 'person_a', 'work_type' => 'production', 'estimated_minutes' => 10],
            ],
        ];
    }
}

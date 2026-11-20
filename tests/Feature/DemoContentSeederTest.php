<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ContentHandoffRecord;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_a_complete_five_idea_week_and_is_idempotent(): void
    {
        Artisan::call('db:seed', ['--class' => DemoContentSeeder::class]);
        Artisan::call('db:seed', ['--class' => DemoContentSeeder::class]);

        $plan = WeeklyPlan::query()
            ->with('inspirations', 'contentIdeas.workAllocations', 'contentIdeas.briefing.versions.approvals', 'contentIdeas.briefing.versions.handoffRecords')
            ->whereDate('week_start', '2026-11-23')
            ->firstOrFail();

        $this->assertCount(3, $plan->inspirations);
        $this->assertCount(5, $plan->contentIdeas);
        $this->assertSame(3, $plan->contentIdeas->where('priority_type.value', 'priority')->count());
        $this->assertSame(2, $plan->contentIdeas->where('priority_type.value', 'reserve')->count());

        $featured = $plan->contentIdeas->firstWhere('title', 'Winter eyewear tips');
        $this->assertNotNull($featured?->briefing);
        $version = $featured->briefing->versions->sole();
        $this->assertSame(ContentVersionStatus::HANDED_OFF, $version->status);
        $this->assertSame(1, $version->approvals->where('decision', ContentApprovalDecision::APPROVED)->count());
        $this->assertCount(1, $version->handoffRecords);
        $this->assertSame(1, ContentVersion::query()->count());
        $this->assertSame(1, ContentHandoffRecord::query()->count());
        $this->assertSame('mock', $plan->input_data['generation']['provider']);
        $this->assertTrue($plan->input_data['generation']['simulated']);
    }

    public function test_dashboard_shows_a_helpful_empty_state_without_a_weekly_plan(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('No demo plan found')
            ->assertSee('DemoContentSeeder');
    }

    public function test_dashboard_and_weekly_report_command_work_with_seeded_demo_data(): void
    {
        Artisan::call('db:seed', ['--class' => DemoContentSeeder::class]);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('KI Social Media')
            ->assertSee('Winter eyewear tips')
            ->assertSee('AI generation: simulated mock')
            ->assertSee('3 priorities · 2 reserves')
            ->assertSee('planned')
            ->assertSee('Briefing: ready');

        $this->get('/weekly-plans/'.WeeklyPlan::query()->firstOrFail()->id.'/report')->assertOk();

        Storage::fake('local');
        $exitCode = Artisan::call('weekly-report:generate');
        $this->assertSame(0, $exitCode);
        Storage::disk('local')->assertExists('weekly-reports/weekly-report-2026-11-23.txt');
    }
}

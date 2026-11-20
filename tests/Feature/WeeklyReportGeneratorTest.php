<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentIdeaPriority;
use App\Enums\WorkAllocationType;
use App\Models\WeeklyPlan;
use App\Services\WeeklyReportGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeeklyReportGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_contains_saved_ideas_capacity_and_simulation_disclaimer(): void
    {
        $plan = $this->createPlanWithIdea();
        $report = app(WeeklyReportGenerator::class)->generate($plan);

        $this->assertStringContainsString('Semaine du : 2026-11-23', $report);
        $this->assertStringContainsString('PRIORITÉS', $report);
        $this->assertStringContainsString('Winter eyewear tips', $report);
        $this->assertStringContainsString('person_a : 30/90 min, reste 60 min', $report);
        $this->assertStringContainsString('Aucune validation enregistrée', $report);
        $this->assertStringContainsString('aucune publication réelle', mb_strtolower($report));
        $this->assertStringContainsString('synthetic-source-001', $report);
        $this->assertStringContainsString('Mesures : Inconnues / non fournies', $report);
    }

    public function test_report_is_exported_as_a_text_file_on_the_local_disk(): void
    {
        Storage::fake('local');
        $plan = $this->createPlanWithIdea();

        $path = app(WeeklyReportGenerator::class)->store($plan);

        $this->assertSame('weekly-reports/weekly-report-2026-11-23.txt', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertStringContainsString(
            'Winter eyewear tips',
            Storage::disk('local')->get($path)
        );
    }

    private function createPlanWithIdea(): WeeklyPlan
    {
        $plan = WeeklyPlan::create([
            'week_start' => '2026-11-23',
            'seasonal_occasion' => 'Winter campaign',
            'input_data' => [],
            'capacity_limits' => ['person_a' => 90, 'person_b' => 150],
        ]);

        $plan->inspirations()->create([
            'channel' => 'instagram',
            'source_id' => 'synthetic-source-001',
            'description' => 'Synthetic winter eyewear inspiration.',
            'metrics' => null,
        ]);

        $idea = $plan->contentIdeas()->create([
            'title' => 'Winter eyewear tips',
            'topic' => 'Practical winter eyewear advice.',
            'target_audience' => 'Adults interested in eyewear',
            'channels' => ['instagram'],
            'format' => 'reel',
            'priority_type' => ContentIdeaPriority::PRIORITY,
            'selection_reason' => 'Useful seasonal topic.',
            'responsible_person' => 'person_a',
            'planned_at' => '2026-11-25',
            'estimated_minutes' => 30,
        ]);

        $idea->workAllocations()->create([
            'person_key' => 'person_a',
            'work_type' => WorkAllocationType::PRODUCTION,
            'estimated_minutes' => 30,
        ]);

        return $plan;
    }
}

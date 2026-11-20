<?php

declare(strict_types=1);

namespace Database\\Seeders;

use App\\Models\\ContentIdea;
use App\\Models\\WeeklyPlan;
use App\\Services\\ContentApprovalService;
use App\\Services\\ContentVersionCreator;
use App\\Services\\SimulatedContentHandoffService;
use Illuminate\\Database\\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $plan = WeeklyPlan::query()->firstOrCreate(
            ['week_start' => '2026-11-23'],
            [
                'seasonal_occasion' => 'Winter eyewear awareness',
                'input_data' => ['source' => 'synthetic-demo', 'locale' => 'de-DE'],
                'capacity_limits' => ['person_a' => 90, 'person_b' => 150],
                'status' => 'ready',
            ],
        );

        $inspirations = [
            ['channel' => 'instagram', 'source_id' => 'synthetic-source-001', 'description' => 'Winter eyewear care: short educational carousel.', 'metrics' => null],
            ['channel' => 'tiktok', 'source_id' => 'synthetic-source-002', 'description' => 'Quick myth-versus-fact video about cold-weather eye comfort.', 'metrics' => null],
            ['channel' => 'facebook', 'source_id' => 'synthetic-source-003', 'description' => 'Community Q&A about glasses, fogging and winter routines.', 'metrics' => null],
        ];

        foreach ($inspirations as $inspiration) {
            $plan->inspirations()->firstOrCreate(
                ['source_id' => $inspiration['source_id']],
                $inspiration,
            );
        }

        $ideas = [
            [
                'title' => 'Winter eyewear tips',
                'topic' => 'Practical advice for caring for glasses in cold weather.',
                'target_audience' => 'People who wear glasses',
                'channels' => ['instagram', 'tiktok'],
                'format' => 'Short video',
                'priority_type' => 'priority',
                'selection_reason' => 'Seasonally relevant and easy to explain visually.',
                'responsible_person' => 'person_a',
                'planned_at' => '2026-11-24',
                'allocations' => [['person_key' => 'person_a', 'work_type' => 'production', 'estimated_minutes' => 30], ['person_key' => 'person_b', 'work_type' => 'variants', 'estimated_minutes' => 20]],
            ],
            [
                'title' => 'How to clean lenses safely',
                'topic' => 'Simple lens-cleaning do and do not guidance.',
                'target_audience' => 'Existing customers',
                'channels' => ['instagram', 'facebook'],
                'format' => 'Carousel',
                'priority_type' => 'priority',
                'selection_reason' => 'Useful evergreen advice with clear visual steps.',
                'responsible_person' => 'person_b',
                'planned_at' => '2026-11-25',
                'allocations' => [['person_key' => 'person_b', 'work_type' => 'production', 'estimated_minutes' => 35]],
            ],
            [
                'title' => 'Screen breaks for focused work',
                'topic' => 'A gentle reminder to take regular screen breaks.',
                'target_audience' => 'Office workers and students',
                'channels' => ['facebook', 'linkedin'],
                'format' => 'Static post',
                'priority_type' => 'priority',
                'selection_reason' => 'Relevant to a broad audience and supports education.',
                'responsible_person' => 'person_a',
                'planned_at' => '2026-11-27',
                'allocations' => [['person_key' => 'person_a', 'work_type' => 'production', 'estimated_minutes' => 25], ['person_key' => 'person_b', 'work_type' => 'coordination', 'estimated_minutes' => 15]],
            ],
            [
                'title' => 'Choosing winter sunglasses',
                'topic' => 'Explain when sunglasses can be useful in winter.',
                'target_audience' => 'Outdoor enthusiasts',
                'channels' => ['instagram'],
                'format' => 'Carousel',
                'priority_type' => 'reserve',
                'selection_reason' => 'Useful seasonal alternative if production time remains.',
                'responsible_person' => 'person_b',
                'planned_at' => '2026-11-28',
                'allocations' => [['person_key' => 'person_b', 'work_type' => 'production', 'estimated_minutes' => 25]],
            ],
            [
                'title' => 'Behind the scenes: frame selection',
                'topic' => 'Show the steps involved in selecting a suitable frame.',
                'target_audience' => 'People considering new frames',
                'channels' => ['instagram', 'facebook'],
                'format' => 'Photo series',
                'priority_type' => 'reserve',
                'selection_reason' => 'Flexible community-oriented reserve idea.',
                'responsible_person' => 'person_b',
                'planned_at' => '2026-11-29',
                'allocations' => [['person_key' => 'person_b', 'work_type' => 'production', 'estimated_minutes' => 25]],
            ],
        ];

        $persistedIdeas = [];
        foreach ($ideas as $data) {
            $idea = ContentIdea::query()->firstOrCreate(
                ['weekly_plan_id' => $plan->id, 'title' => $data['title']],
                collect($data)->except('allocations')->all() + ['estimated_minutes' => collect($data['allocations'])->sum('estimated_minutes')],
            );

            foreach ($data['allocations'] as $allocation) {
                $idea->workAllocations()->firstOrCreate(
                    ['person_key' => $allocation['person_key'], 'work_type' => $allocation['work_type']],
                    ['estimated_minutes' => $allocation['estimated_minutes']],
                );
            }

            $persistedIdeas[$data['title']] = $idea;
        }

        $priority = $persistedIdeas['Winter eyewear tips'];
        $briefing = $priority->briefing()->firstOrCreate(
            ['content_idea_id' => $priority->id],
            [
                'hook' => 'Brille beschlägt im Winter? Mit diesen drei einfachen Tipps bleibt der Durchblick klar.',
                'body' => 'Kalte Luft und warme Räume können Brillengläser beschlagen lassen. Reinige deine Gläser mit einem geeigneten Mikrofasertuch, bewahre deine Brille sicher auf und vermeide raue Materialien. Bei Fragen zur passenden Pflege hilft dir unser Optik-Team gerne weiter.',
                'filming_plan' => '1. Nahaufnahme beschlagener Brillengläser. 2. Drei kurze Tipps als Szenen zeigen. 3. Abschluss mit freundlicher Einladung zur Beratung.',
                'on_screen_text' => ['3 Wintertipps für deine Brille', 'Mikrofasertuch verwenden', 'Brille sicher aufbewahren', 'Bei Fragen beraten lassen'],
                'cover_idea' => 'Brille vor winterlichem Fenster, Titel: Klarer Durchblick im Winter',
                'cta_text' => 'Speichere dir die Tipps und frage unser Team bei Bedarf.',
                'appointment_url' => 'https://example.test/beratung',
                'status' => 'ready',
            ],
        );

        if (! $briefing->versions()->exists()) {
            $version = app(ContentVersionCreator::class)->create($briefing, [
                'hook' => $briefing->hook,
                'body' => $briefing->body,
                'channel_variants' => [
                    'instagram' => ['text' => 'Winter-Check für deine Brille ❄️ Mikrofasertuch nutzen, sicher aufbewahren und den Durchblick behalten. #Brillenpflege'],
                    'tiktok' => ['text' => 'Beschlagene Brille im Winter? Drei einfache Pflegetipps für klaren Durchblick. Folge für mehr alltagstaugliche Optik-Tipps.'],
                ],
                'media_reference' => 'synthetic-media/winter-eyewear-tips.mp4',
                'channels' => ['instagram', 'tiktok'],
                'scheduled_at' => '2026-11-24',
            ]);

            $approvalService = app(ContentApprovalService::class);
            $approvalService->submitForReview($version);
            $version = $approvalService->approve($version, 'Demo reviewer', 'Synthetic demonstration approval.');
            app(SimulatedContentHandoffService::class)->handoff($version);
        }
    }
}

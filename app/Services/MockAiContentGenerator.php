<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AiContentGeneratorInterface;
use DateTimeImmutable;

/**
 * Deterministic, synthetic AI substitute for demos and automated tests.
 *
 * These are fixed test answers with small context-aware text substitutions.
 * No model, network request, trend research or external data is involved.
 */
class MockAiContentGenerator implements AiContentGeneratorInterface
{
    public function generateWeeklyPlan(array $context): array
    {
        $weekStart = new DateTimeImmutable((string) ($context['week_start'] ?? '2026-11-23'));
        $themes = array_values($context['themes'] ?? []);
        $audience = (string) ($context['target_audience'] ?? 'People interested in eyewear');
        $season = trim((string) ($context['seasonal_occasion'] ?? 'everyday eyewear'));

        $definitions = [
            [
                'title' => 'Winter eyewear tips',
                'topic' => $themes[0] ?? "Practical advice for caring for glasses during {$season}.",
                'channels' => ['instagram', 'tiktok'],
                'format' => 'Short video',
                'priority_type' => 'priority',
                'selection_reason' => 'Seasonally relevant and easy to explain visually.',
                'responsible_person' => 'person_a',
                'day' => 1,
                'allocations' => [
                    ['person_key' => 'person_a', 'work_type' => 'production', 'estimated_minutes' => 30],
                    ['person_key' => 'person_b', 'work_type' => 'variants', 'estimated_minutes' => 20],
                ],
            ],
            [
                'title' => 'How to clean lenses safely',
                'topic' => $themes[1] ?? 'Simple lens-cleaning do and do not guidance.',
                'channels' => ['instagram', 'facebook'],
                'format' => 'Carousel',
                'priority_type' => 'priority',
                'selection_reason' => 'Useful evergreen advice with clear visual steps.',
                'responsible_person' => 'person_b',
                'day' => 2,
                'allocations' => [
                    ['person_key' => 'person_b', 'work_type' => 'production', 'estimated_minutes' => 35],
                ],
            ],
            [
                'title' => 'Screen breaks for focused work',
                'topic' => $themes[2] ?? 'A gentle reminder to take regular screen breaks.',
                'channels' => ['facebook', 'linkedin'],
                'format' => 'Static post',
                'priority_type' => 'priority',
                'selection_reason' => 'Relevant to a broad audience and supports educational content.',
                'responsible_person' => 'person_a',
                'day' => 4,
                'allocations' => [
                    ['person_key' => 'person_a', 'work_type' => 'production', 'estimated_minutes' => 25],
                    ['person_key' => 'person_b', 'work_type' => 'coordination', 'estimated_minutes' => 15],
                ],
            ],
            [
                'title' => 'Choosing winter sunglasses',
                'topic' => $themes[3] ?? 'Explain when sunglasses can be useful in winter.',
                'channels' => ['instagram'],
                'format' => 'Carousel',
                'priority_type' => 'reserve',
                'selection_reason' => 'Useful seasonal alternative if production time remains.',
                'responsible_person' => 'person_b',
                'day' => 5,
                'allocations' => [
                    ['person_key' => 'person_b', 'work_type' => 'production', 'estimated_minutes' => 25],
                ],
            ],
            [
                'title' => 'Behind the scenes: frame selection',
                'topic' => $themes[4] ?? 'Show the steps involved in selecting a suitable frame.',
                'channels' => ['instagram', 'facebook'],
                'format' => 'Photo series',
                'priority_type' => 'reserve',
                'selection_reason' => 'Flexible community-oriented reserve idea.',
                'responsible_person' => 'person_b',
                'day' => 6,
                'allocations' => [
                    ['person_key' => 'person_b', 'work_type' => 'production', 'estimated_minutes' => 25],
                ],
            ],
        ];

        $ideas = array_map(
            static function (array $idea) use ($weekStart, $audience): array {
                $day = $idea['day'];
                unset($idea['day']);

                return [
                    ...$idea,
                    'target_audience' => $audience,
                    'planned_at' => $weekStart->modify("+{$day} days")->format('Y-m-d'),
                ];
            },
            $definitions,
        );

        return ['ideas' => $ideas];
    }

    public function generateBriefing(array $context): array
    {
        $idea = $context['idea'] ?? [];
        $title = (string) ($idea['title'] ?? 'Eyewear tips');
        $topic = (string) ($idea['topic'] ?? 'Practical eyewear advice');
        $channels = $idea['channels'] ?? ['instagram', 'tiktok'];
        $tone = (string) ($context['tone'] ?? 'friendly, personal and helpful');

        $variants = [];
        foreach ($channels as $channel) {
            $variants[$channel] = match ($channel) {
                'instagram' => [
                    'text' => "{$title}: {$topic} Speichere dir diese Tipps für später. #BrillenTipps",
                    'format' => 'carousel',
                ],
                'tiktok' => [
                    'text' => "Kurz erklärt: {$topic} Folge uns für weitere praktische Optik-Tipps.",
                    'format' => 'short_video',
                ],
                'facebook' => [
                    'text' => "{$topic} Was hilft dir im Alltag? Teile deine Erfahrungen mit unserer Community.",
                    'format' => 'community_post',
                ],
                'linkedin' => [
                    'text' => "{$topic} Ein kurzer, alltagsnaher Impuls rund um gutes Sehen und bewusstes Arbeiten.",
                    'format' => 'professional_post',
                ],
                default => [
                    'text' => "{$title}: {$topic}",
                    'format' => 'post',
                ],
            };
        }

        return [
            'hook' => "Kennst du das? {$title} – wir zeigen dir, worauf du achten kannst.",
            'body' => "{$topic} Wir erklären die wichtigsten Punkte verständlich und ohne Fachjargon. Unser Team unterstützt dich gerne mit einer persönlichen Beratung. Tonalität: {$tone}.",
            'filming_plan' => '1. Mit einer kurzen Alltagssituation starten. 2. Zwei bis drei konkrete Schritte als Nahaufnahme zeigen. 3. Mit einer freundlichen Einladung zur persönlichen Beratung abschliessen.',
            'on_screen_text' => [
                'Das solltest du wissen',
                'Einfach erklärt',
                'Praktischer Tipp für den Alltag',
                'Fragen? Wir beraten dich gerne',
            ],
            'cover_idea' => "Helles, aufgeräumtes Motiv mit einer Brille und dem Titel: {$title}.",
            'cta_text' => 'Vereinbare einen persönlichen Beratungstermin.',
            'appointment_url' => 'https://example.com/beratung',
            'channel_variants' => $variants,
            'media_reference' => 'synthetic-media/ai-generated-demo.mp4',
        ];
    }
}

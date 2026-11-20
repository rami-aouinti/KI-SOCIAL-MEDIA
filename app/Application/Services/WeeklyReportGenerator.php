<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Shared\Enums\ContentIdeaPriority;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Support\Facades\Storage;

class WeeklyReportGenerator
{
    public function __construct(
        private readonly CapacityPlanner $capacityPlanner,
    ) {}

    /**
     * Build a human-readable report from persisted records only.
     */
    public function generate(WeeklyPlan $weeklyPlan): string
    {
        // Route model binding (or a caller) may already provide a loaded plan.
        // Load only missing relations and avoid re-querying the weekly_plans row.
        $weeklyPlan->loadMissing([
            'inspirations',
            'contentIdeas.workAllocations',
            'contentIdeas.briefing.versions.approvals',
            'contentIdeas.briefing.versions.handoffRecords',
        ]);

        $plan = $weeklyPlan;

        $allocations = $plan->contentIdeas
            ->flatMap(static fn ($idea) => $idea->workAllocations)
            ->map(static fn ($allocation): array => [
                'person_key' => $allocation->person_key,
                'estimated_minutes' => $allocation->estimated_minutes,
            ])
            ->all();
        $capacity = $this->capacityPlanner->analyze($plan->capacity_limits ?? [], $allocations);
        $lines = [
            'KI SOCIAL MEDIA — RAPPORT HEBDOMADAIRE',
            str_repeat('=', 48),
            'Semaine du : '.$plan->week_start->toDateString(),
            'Contexte saisonnier : '.($plan->seasonal_occasion ?: 'Non renseigné'),
            'Statut du plan : '.$this->enumValue($plan->status),
            '',
            '1. INSPIRATIONS ENREGISTRÉES',
            str_repeat('-', 32),
        ];

        if ($plan->inspirations->isEmpty()) {
            $lines[] = 'Aucune référence enregistrée.';
        } else {
            foreach ($plan->inspirations as $reference) {
                $lines[] = sprintf(
                    '- [%s] %s — %s',
                    $reference->channel,
                    $reference->source_id,
                    $reference->description
                );
                $lines[] = '  Mesures : '.($reference->metrics === null
                    ? 'Inconnues / non fournies'
                    : json_encode($reference->metrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        $lines[] = '';
        $lines[] = '2. PLANNING ÉDITORIAL';
        $lines[] = str_repeat('-', 32);

        foreach ([ContentIdeaPriority::PRIORITY, ContentIdeaPriority::RESERVE] as $priority) {
            $lines[] = $priority === ContentIdeaPriority::PRIORITY
                ? 'PRIORITÉS'
                : 'RÉSERVES';

            $ideas = $plan->contentIdeas
                ->filter(fn ($idea): bool => $idea->priority_type === $priority)
                ->sortBy('planned_at');

            if ($ideas->isEmpty()) {
                $lines[] = '- Aucune idée enregistrée.';
            }

            foreach ($ideas as $idea) {
                $lines[] = sprintf(
                    '- %s | %s | %s | %s | %d min',
                    $idea->title,
                    $idea->planned_at->toDateString(),
                    implode(', ', $idea->channels ?? []),
                    $idea->responsible_person,
                    $idea->estimated_minutes
                );
                $lines[] = '  Statut : '.$this->enumValue($idea->status);
                $lines[] = '  Justification : '.($idea->selection_reason ?: 'Non renseignée');

                $versions = $idea->briefing?->versions ?? collect();
                $latestVersion = $versions->sortByDesc('version_number')->first();

                if (! $latestVersion) {
                    $lines[] = '  Contenu : aucun briefing/version enregistré.';
                    $lines[] = '  Validation : Aucune validation enregistrée';
                    $lines[] = '  Transmission : Aucune transmission enregistrée';

                    continue;
                }

                $lines[] = sprintf(
                    '  Version : v%d (%s)',
                    $latestVersion->version_number,
                    $this->enumValue($latestVersion->status)
                );

                $approval = $latestVersion->approvals
                    ->sortByDesc('reviewed_at')
                    ->first();

                $lines[] = '  Validation : '.($approval
                    ? sprintf('%s par %s le %s', $this->enumValue($approval->decision), $approval->reviewer, $approval->reviewed_at->toDateTimeString())
                    : 'Aucune validation enregistrée');

                $handoff = $latestVersion->handoffRecords->sortByDesc('handed_off_at')->first();
                $lines[] = '  Transmission : '.($handoff
                    ? sprintf('%s (simulée) le %s', $handoff->status, $handoff->handed_off_at->toDateTimeString())
                    : 'Aucune transmission enregistrée');
            }
        }

        $lines[] = '';
        $lines[] = '3. CAPACITÉ DE L’ÉQUIPE';
        $lines[] = str_repeat('-', 32);

        foreach ($capacity['people'] as $person => $details) {
            $capacityText = $details['capacity_minutes'] === null
                ? 'capacité non configurée'
                : sprintf(
                    '%d/%d min, reste %d min',
                    $details['allocated_minutes'],
                    $details['capacity_minutes'],
                    $details['remaining_minutes']
                );

            $lines[] = sprintf('- %s : %s', $person, $capacityText);
        }

        if ($capacity['warnings'] === []) {
            $lines[] = 'Aucune alerte de capacité.';
        } else {
            $lines[] = 'Alertes :';
            foreach ($capacity['warnings'] as $warning) {
                $lines[] = '- '.$warning['message'];
            }
        }

        $lines[] = '';
        $lines[] = '4. PÉRIMÈTRE ET LIMITES';
        $lines[] = str_repeat('-', 32);
        $lines[] = '- Les références et données sont celles enregistrées dans cette démonstration.';
        $lines[] = '- Les transmissions sont simulées : aucune publication réelle n’a été effectuée.';
        $lines[] = '- Les métriques absentes restent inconnues et ne sont pas estimées.';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    /**
     * Store the report on the local Laravel disk and return its relative path.
     */
    public function store(WeeklyPlan $weeklyPlan): string
    {
        $week = $weeklyPlan->week_start->toDateString();
        $path = sprintf('weekly-reports/weekly-report-%s.txt', $week);

        Storage::disk('local')->put($path, $this->generate($weeklyPlan));

        return $path;
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}

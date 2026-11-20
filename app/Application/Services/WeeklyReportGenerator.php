<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\WeeklyReportDataSourceInterface;
use App\Application\Contracts\WeeklyReportStorageInterface;
use App\Application\DTO\WeeklyReportData;
use App\Domain\Shared\Enums\ContentIdeaPriority;

class WeeklyReportGenerator
{
    public function __construct(
        private readonly CapacityPlanner $capacityPlanner,
        private readonly WeeklyReportDataSourceInterface $reports,
        private readonly WeeklyReportStorageInterface $storage,
    ) {}

    public function generate(int $weeklyPlanId): string
    {
        return $this->render($this->reports->find($weeklyPlanId));
    }

    public function store(int $weeklyPlanId): string
    {
        $report = $this->reports->find($weeklyPlanId);
        $path = sprintf('weekly-reports/weekly-report-%s.txt', $report->weekStart);

        $this->storage->put($path, $this->render($report));

        return $path;
    }

    private function render(WeeklyReportData $report): string
    {
        $allocations = [];

        foreach ($report->ideas as $idea) {
            foreach ($idea['allocations'] as $allocation) {
                $allocations[] = $allocation;
            }
        }

        $capacity = $this->capacityPlanner->analyze($report->capacityLimits, $allocations);
        $lines = [
            'KI SOCIAL MEDIA — RAPPORT HEBDOMADAIRE',
            str_repeat('=', 48),
            'Semaine du : '.$report->weekStart,
            'Contexte saisonnier : '.($report->seasonalOccasion ?: 'Non renseigné'),
            'Statut du plan : '.$report->status,
            '',
            '1. INSPIRATIONS ENREGISTRÉES',
            str_repeat('-', 32),
        ];

        if ($report->inspirations === []) {
            $lines[] = 'Aucune référence enregistrée.';
        } else {
            foreach ($report->inspirations as $reference) {
                $lines[] = sprintf(
                    '- [%s] %s — %s',
                    $reference['channel'],
                    $reference['source_id'],
                    $reference['description'],
                );
                $lines[] = '  Mesures : '.($reference['metrics'] === null
                    ? 'Inconnues / non fournies'
                    : json_encode($reference['metrics'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        $lines[] = '';
        $lines[] = '2. PLANNING ÉDITORIAL';
        $lines[] = str_repeat('-', 32);

        foreach ([ContentIdeaPriority::PRIORITY->value, ContentIdeaPriority::RESERVE->value] as $priority) {
            $lines[] = $priority === ContentIdeaPriority::PRIORITY->value
                ? 'PRIORITÉS'
                : 'RÉSERVES';

            $ideas = array_values(array_filter(
                $report->ideas,
                static fn (array $idea): bool => $idea['priority_type'] === $priority,
            ));
            usort(
                $ideas,
                static fn (array $left, array $right): int => $left['planned_at'] <=> $right['planned_at'],
            );

            if ($ideas === []) {
                $lines[] = '- Aucune idée enregistrée.';
            }

            foreach ($ideas as $idea) {
                $lines[] = sprintf(
                    '- %s | %s | %s | %s | %d min',
                    $idea['title'],
                    $idea['planned_at'],
                    implode(', ', $idea['channels']),
                    $idea['responsible_person'],
                    $idea['estimated_minutes'],
                );
                $lines[] = '  Statut : '.$idea['status'];
                $lines[] = '  Justification : '.($idea['selection_reason'] ?: 'Non renseignée');

                $version = $idea['latest_version'];

                if ($version === null) {
                    $lines[] = '  Contenu : aucun briefing/version enregistré.';
                    $lines[] = '  Validation : Aucune validation enregistrée';
                    $lines[] = '  Transmission : Aucune transmission enregistrée';

                    continue;
                }

                $lines[] = sprintf(
                    '  Version : v%d (%s)',
                    $version['version_number'],
                    $version['status'],
                );

                $approval = $version['approval'];
                $lines[] = '  Validation : '.($approval !== null
                    ? sprintf(
                        '%s par %s le %s',
                        $approval['decision'],
                        $approval['reviewer'],
                        $approval['reviewed_at'],
                    )
                    : 'Aucune validation enregistrée');

                $handoff = $version['handoff'];
                $lines[] = '  Transmission : '.($handoff !== null
                    ? sprintf('%s (simulée) le %s', $handoff['status'], $handoff['handed_off_at'])
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
                    $details['remaining_minutes'],
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
}

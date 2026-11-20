<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class WeeklyReportData
{
    /**
     * @param array<string, int> $capacityLimits
     * @param list<array{channel: string, source_id: string, description: string, metrics: array<string, mixed>|null}> $inspirations
     * @param list<array{
     *     title: string,
     *     planned_at: string,
     *     channels: list<string>,
     *     responsible_person: string,
     *     estimated_minutes: int,
     *     priority_type: string,
     *     status: string,
     *     selection_reason: string|null,
     *     allocations: list<array{person_key: string, estimated_minutes: int}>,
     *     latest_version: array{
     *         version_number: int,
     *         status: string,
     *         approval: array{decision: string, reviewer: string, reviewed_at: string}|null,
     *         handoff: array{status: string, handed_off_at: string}|null
     *     }|null
     * }> $ideas
     */
    public function __construct(
        public string $weekStart,
        public ?string $seasonalOccasion,
        public string $status,
        public array $capacityLimits,
        public array $inspirations,
        public array $ideas,
    ) {}
}

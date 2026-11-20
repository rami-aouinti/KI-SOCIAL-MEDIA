<?php

declare(strict_types=1);

namespace App\Application\Services;

final class CapacityPlanner
{
    /**
     * @param  array<string, int|numeric-string|null>  $capacityLimits
     * @param  iterable<array{person_key: string, estimated_minutes: int}>  $allocations
     * @return array{
     *     within_capacity: bool,
     *     people: array<string, array{
     *         allocated_minutes: int,
     *         capacity_minutes: int|null,
     *         remaining_minutes: int|null,
     *         exceeded_by_minutes: int,
     *         is_over_capacity: bool
     *     }>,
     *     warnings: list<array{type: string, person_key: string, message: string}>
     * }
     */
    public function analyze(array $capacityLimits, iterable $allocations): array
    {
        $allocationsByPerson = [];

        foreach ($allocations as $allocation) {
            $personKey = $allocation['person_key'];
            $allocationsByPerson[$personKey] = ($allocationsByPerson[$personKey] ?? 0)
                + $allocation['estimated_minutes'];
        }

        $personKeys = array_unique([
            ...array_keys($capacityLimits),
            ...array_keys($allocationsByPerson),
        ]);

        $people = [];
        $warnings = [];

        foreach ($personKeys as $personKey) {
            $allocatedMinutes = $allocationsByPerson[$personKey] ?? 0;
            $capacity = $capacityLimits[$personKey] ?? null;

            if (is_numeric($capacity) === false || (int) $capacity < 0) {
                $people[$personKey] = [
                    'allocated_minutes' => $allocatedMinutes,
                    'capacity_minutes' => null,
                    'remaining_minutes' => null,
                    'exceeded_by_minutes' => 0,
                    'is_over_capacity' => false,
                ];

                $warnings[] = [
                    'type' => 'capacity_missing',
                    'person_key' => $personKey,
                    'message' => sprintf('No valid weekly capacity is configured for %s.', $personKey),
                ];

                continue;
            }

            $capacity = (int) $capacity;
            $exceededBy = max(0, $allocatedMinutes - $capacity);
            $isOverCapacity = $exceededBy > 0;

            $people[$personKey] = [
                'allocated_minutes' => $allocatedMinutes,
                'capacity_minutes' => $capacity,
                'remaining_minutes' => max(0, $capacity - $allocatedMinutes),
                'exceeded_by_minutes' => $exceededBy,
                'is_over_capacity' => $isOverCapacity,
            ];

            if ($isOverCapacity) {
                $warnings[] = [
                    'type' => 'capacity_exceeded',
                    'person_key' => $personKey,
                    'message' => sprintf(
                        '%s is over the weekly capacity by %d minutes (%d/%d minutes).',
                        $personKey,
                        $exceededBy,
                        $allocatedMinutes,
                        $capacity,
                    ),
                ];
            }
        }

        return [
            'within_capacity' => $warnings === [],
            'people' => $people,
            'warnings' => $warnings,
        ];
    }
}

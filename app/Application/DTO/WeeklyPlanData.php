<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class WeeklyPlanData
{
    /**
     * @param array<string, int> $capacityLimits
     * @param list<InspirationData> $inspirations
     * @param list<ContentIdeaData> $ideas
     * @param array<string, mixed> $inputData
     */
    public function __construct(
        public string $weekStart,
        public ?string $seasonalOccasion,
        public array $capacityLimits,
        public array $inspirations,
        public array $ideas,
        public array $inputData,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromValidatedArray(array $data): self
    {
        return new self(
            $data['week_start'],
            $data['seasonal_occasion'] ?? null,
            $data['capacity_limits'],
            array_map(
                static fn (array $item): InspirationData => InspirationData::fromArray($item),
                $data['inspirations'],
            ),
            array_map(
                static fn (array $item): ContentIdeaData => ContentIdeaData::fromArray($item),
                $data['ideas'],
            ),
            $data,
        );
    }

    /** @return list<array<string, mixed>> */
    public function inspirationRows(): array
    {
        return array_map(
            static fn (InspirationData $item): array => $item->toArray(),
            $this->inspirations,
        );
    }
}

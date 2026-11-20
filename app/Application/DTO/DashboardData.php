<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class DashboardData
{
    /**
     * @param array<string, mixed>|null $plan
     * @param array<string, int|numeric-string|null> $capacityLimits
     * @param list<array{person_key: string, estimated_minutes: int}> $allocations
     * @param array<string, mixed>|null $capacity
     */
    public function __construct(
        public ?array $plan,
        public array $capacityLimits = [],
        public array $allocations = [],
        public ?array $capacity = null,
    ) {
    }

    /** @param array<string, mixed> $capacity */
    public function withCapacity(array $capacity): self
    {
        return new self($this->plan, $this->capacityLimits, $this->allocations, $capacity);
    }
}

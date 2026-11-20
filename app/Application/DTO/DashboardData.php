<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class DashboardData
{
    public function __construct(
        public ?array $plan,
        public array $capacityLimits = [],
        public array $allocations = [],
        public ?array $capacity = null,
    ) {}

    public function withCapacity(array $capacity): self
    {
        return new self($this->plan, $this->capacityLimits, $this->allocations, $capacity);
    }
}

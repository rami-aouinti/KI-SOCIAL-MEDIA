<?php

declare(strict_types=1);

namespace App\Domain\Planning\Entities;

use App\Domain\Shared\Enums\WeeklyPlanStatus;
use DomainException;

final readonly class WeeklyPlan
{
    public function __construct(
        public ?int $id,
        public string $weekStart,
        public WeeklyPlanStatus $status = WeeklyPlanStatus::DRAFT,
    ) {
        if ($weekStart === '') {
            throw new \InvalidArgumentException('A weekly plan must have a start date.');
        }
    }

    public function markReady(int $priorityIdeas, int $reserveIdeas): self
    {
        if ($this->status !== WeeklyPlanStatus::DRAFT) {
            throw new DomainException('Only draft weekly plans can be marked ready.');
        }

        if ($priorityIdeas !== 3 || $reserveIdeas !== 2) {
            throw new DomainException('A weekly plan must contain exactly three priority ideas and two reserve ideas.');
        }

        return new self($this->id, $this->weekStart, WeeklyPlanStatus::READY);
    }

    public function complete(): self
    {
        if ($this->status !== WeeklyPlanStatus::READY) {
            throw new DomainException('Only ready weekly plans can be completed.');
        }

        return new self($this->id, $this->weekStart, WeeklyPlanStatus::COMPLETED);
    }
}

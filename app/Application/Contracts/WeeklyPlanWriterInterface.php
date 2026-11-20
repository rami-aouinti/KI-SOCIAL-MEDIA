<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Application\DTO\WeeklyPlanData;

interface WeeklyPlanWriterInterface
{
    /** @return object Persisted plan with its inspirations, ideas and allocations loaded. */
    public function create(WeeklyPlanData $data): mixed;
}

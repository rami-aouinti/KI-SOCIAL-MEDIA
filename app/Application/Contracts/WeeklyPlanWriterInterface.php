<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Application\DTO\WeeklyPlanData;

interface WeeklyPlanWriterInterface
{
    /** Persist a complete plan and return its transport-ready persistence representation. */
    public function create(WeeklyPlanData $data): object;
}

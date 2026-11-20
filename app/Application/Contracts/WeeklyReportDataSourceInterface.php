<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface WeeklyReportDataSourceInterface
{
    /** Load the persisted plan and the relations required by the report. */
    public function load(mixed $weeklyPlan): mixed;
}

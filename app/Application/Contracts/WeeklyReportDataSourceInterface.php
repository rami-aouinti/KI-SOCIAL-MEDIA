<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Application\DTO\WeeklyReportData;

interface WeeklyReportDataSourceInterface
{
    public function find(int $weeklyPlanId): WeeklyReportData;
}

<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Application\DTO\DashboardData;

interface DashboardDataSourceInterface
{
    public function load(): DashboardData;
}

<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\DashboardDataSourceInterface;
use App\Application\DTO\DashboardData;

final readonly class DashboardQueryService
{
    public function __construct(
        private DashboardDataSourceInterface $dataSource,
        private CapacityPlanner $capacityPlanner,
    ) {
    }

    public function getDashboard(): DashboardData
    {
        $data = $this->dataSource->load();

        if ($data->plan === null) {
            return $data;
        }

        return $data->withCapacity(
            $this->capacityPlanner->analyze($data->capacityLimits, $data->allocations),
        );
    }
}

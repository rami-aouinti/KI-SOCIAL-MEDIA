<?php

declare(strict_types=1);

namespace App\Transport\Http\Controllers;

use App\Application\Services\CapacityPlanner;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(CapacityPlanner $capacityPlanner): View
    {
        $plan = WeeklyPlan::query()
            ->with([
                'inspirations',
                'contentIdeas.workAllocations',
                'contentIdeas.briefing.versions.approvals',
                'contentIdeas.briefing.versions.handoffRecords',
            ])
            ->latest('week_start')
            ->first();

        $capacity = $plan ? $capacityPlanner->analyze($plan) : null;

        return view('welcome', ['plan' => $plan, 'capacity' => $capacity]);
    }
}

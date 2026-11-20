<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WeeklyPlan;
use App\Services\CapacityPlanner;
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

        return view('welcome', compact('plan', 'capacity'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WeeklyPlan;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
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

        return view('welcome', compact('plan'));
    }
}

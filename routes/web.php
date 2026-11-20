<?php

use App\\Models\\WeeklyPlan;
use App\\Services\\WeeklyReportGenerator;
use Illuminate\\Support\\Facades\\Route;
use Illuminate\\Support\\Facades\\Storage;

Route::get('/', function () {
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
});

Route::get('/weekly-plans/{weeklyPlan}/report', function (
    WeeklyPlan $weeklyPlan,
    WeeklyReportGenerator $generator,
) {
    $path = $generator->store($weeklyPlan);

    return Storage::disk('local')->download($path);
})->name('weekly-plans.report');

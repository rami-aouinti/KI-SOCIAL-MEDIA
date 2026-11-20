<?php

use App\Transport\Http\Controllers\DashboardController;
use App\Transport\Http\Controllers\WeeklyPlanReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get(
    '/weekly-plans/{weeklyPlan}/report',
    WeeklyPlanReportController::class,
)->name('weekly-plans.report');

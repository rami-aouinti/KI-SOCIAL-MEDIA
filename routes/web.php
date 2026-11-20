<?php

use App\Transport\Http\Controllers\Auth\LoginController;
use App\Transport\Http\Controllers\DashboardController;
use App\Transport\Http\Controllers\WeeklyPlanReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get(
        '/weekly-plans/{weeklyPlan}/report',
        WeeklyPlanReportController::class,
    )->whereNumber('weeklyPlan')->name('weekly-plans.report');
});

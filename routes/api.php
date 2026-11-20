<?php

use App\Transport\Http\Controllers\Api\V1\ContentIdeaController;
use App\Transport\Http\Controllers\Api\V1\ContentVersionController;
use App\Transport\Http\Controllers\Api\V1\WeeklyPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['api.token', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/weekly-plans', [WeeklyPlanController::class, 'index'])
            ->middleware('api.ability:plans:read');
        Route::get('/weekly-plans/{weeklyPlan}', [WeeklyPlanController::class, 'show'])
            ->whereNumber('weeklyPlan')
            ->middleware('api.ability:plans:read');
        Route::post('/weekly-plans', [WeeklyPlanController::class, 'store'])
            ->middleware('api.ability:plans:write');

        Route::get('/content-ideas/{contentIdea}', [ContentIdeaController::class, 'show'])
            ->whereNumber('contentIdea')
            ->middleware('api.ability:content:read');

        Route::get('/content-versions/{contentVersion}', [ContentVersionController::class, 'show'])
            ->whereNumber('contentVersion')
            ->middleware('api.ability:content:read');
        Route::post('/content-briefings/{briefing}/versions', [ContentVersionController::class, 'store'])
            ->whereNumber('briefing')
            ->middleware('api.ability:content:write');
        Route::post('/content-versions/{contentVersion}/submit-review', [ContentVersionController::class, 'submitForReview'])
            ->whereNumber('contentVersion')
            ->middleware('api.ability:content:write');
        Route::post('/content-versions/{contentVersion}/approve', [ContentVersionController::class, 'approve'])
            ->whereNumber('contentVersion')
            ->middleware('api.ability:content:approve');
        Route::post('/content-versions/{contentVersion}/reject', [ContentVersionController::class, 'reject'])
            ->whereNumber('contentVersion')
            ->middleware('api.ability:content:approve');
    });

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WeeklyPlan;
use App\Services\WeeklyReportGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyPlanReportController extends Controller
{
    public function __invoke(
        WeeklyPlan $weeklyPlan,
        WeeklyReportGenerator $generator,
    ): StreamedResponse {
        $path = $generator->store($weeklyPlan);

        return Storage::disk('local')->download($path);
    }
}

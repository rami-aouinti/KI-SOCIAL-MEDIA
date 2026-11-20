<?php

declare(strict_types=1);

namespace App\Transport\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use App\Application\Services\WeeklyReportGenerator;
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

<?php

declare(strict_types=1);

namespace App\Transport\Http\Controllers;

use App\Application\Services\WeeklyReportGenerator;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyPlanReportController extends Controller
{
    public function __invoke(
        WeeklyPlan $weeklyPlan,
        WeeklyReportGenerator $generator,
    ): StreamedResponse {
        $path = $generator->store((int) $weeklyPlan->getKey());

        return Storage::disk('local')->download($path);
    }
}

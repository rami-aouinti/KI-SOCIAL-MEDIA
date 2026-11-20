<?php

declare(strict_types=1);

namespace App\Transport\Http\Controllers;

use App\Application\Services\DashboardQueryService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardQueryService $dashboardQuery): View
    {
        return view('welcome', [
            'dashboard' => $dashboardQuery->getDashboard(),
        ]);
    }
}

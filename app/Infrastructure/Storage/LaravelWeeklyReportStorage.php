<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Contracts\WeeklyReportStorageInterface;
use Illuminate\Support\Facades\Storage;

final class LaravelWeeklyReportStorage implements WeeklyReportStorageInterface
{
    public function put(string $path, string $contents): void
    {
        Storage::disk('local')->put($path, $contents);
    }
}

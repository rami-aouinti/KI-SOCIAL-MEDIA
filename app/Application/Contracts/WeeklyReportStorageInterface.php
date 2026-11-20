<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface WeeklyReportStorageInterface
{
    public function put(string $path, string $contents): void;
}

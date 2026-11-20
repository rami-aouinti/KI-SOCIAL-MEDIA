<?php

declare(strict_types=1);

namespace App\Infrastructure\Transactions;

use App\Domain\Transactions\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function run(callable $operation): mixed
    {
        return DB::transaction($operation);
    }
}

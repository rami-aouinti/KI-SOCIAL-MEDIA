<?php

declare(strict_types=1);

namespace App\Domain\Transactions;

interface TransactionManagerInterface
{
    /**
     * @template TResult
     * @param callable(): TResult $operation
     * @return TResult
     */
    public function run(callable $operation): mixed;
}

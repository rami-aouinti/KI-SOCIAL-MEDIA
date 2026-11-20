<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\ContentHandoffRecord;

interface ContentHandoffRepositoryInterface
{
    public function findForVersion(int $versionId): ?ContentHandoffRecord;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): ContentHandoffRecord;
}

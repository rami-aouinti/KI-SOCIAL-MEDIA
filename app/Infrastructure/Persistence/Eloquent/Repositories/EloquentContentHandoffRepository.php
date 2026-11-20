<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\ContentHandoffRecord;
use App\Domain\Repositories\ContentHandoffRepositoryInterface;

class EloquentContentHandoffRepository implements ContentHandoffRepositoryInterface
{
    public function findForVersion(int $versionId): ?ContentHandoffRecord
    {
        return ContentHandoffRecord::query()
            ->where('content_version_id', $versionId)
            ->first();
    }

    public function create(array $attributes): ContentHandoffRecord
    {
        return ContentHandoffRecord::query()->create($attributes);
    }
}

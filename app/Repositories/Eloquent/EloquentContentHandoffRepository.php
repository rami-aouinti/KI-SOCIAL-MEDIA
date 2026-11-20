<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ContentHandoffRecord;
use App\Repositories\Contracts\ContentHandoffRepositoryInterface;

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

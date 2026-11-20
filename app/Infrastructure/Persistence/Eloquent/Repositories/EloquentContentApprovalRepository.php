<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Enums\ContentApprovalDecision;
use App\Models\ContentVersionApproval;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;

class EloquentContentApprovalRepository implements ContentApprovalRepositoryInterface
{
    public function create(array $attributes): ContentVersionApproval
    {
        return ContentVersionApproval::query()->create($attributes);
    }

    public function findLatestForVersionAndHash(
        int $versionId,
        ContentApprovalDecision $decision,
        string $contentHash,
    ): ?ContentVersionApproval {
        return ContentVersionApproval::query()
            ->where('content_version_id', $versionId)
            ->where('decision', $decision->value)
            ->where('content_hash', $contentHash)
            ->latest('reviewed_at')
            ->first();
    }
}

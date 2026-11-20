<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Content\Records\ContentApprovalRecord;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;
use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersionApproval;

final class EloquentContentApprovalRepository implements ContentApprovalRepositoryInterface
{
    public function create(array $attributes): ContentApprovalRecord
    {
        return $this->toRecord(ContentVersionApproval::query()->create($attributes));
    }

    public function findLatestForVersionAndHash(
        int $versionId,
        ContentApprovalDecision $decision,
        string $contentHash,
    ): ?ContentApprovalRecord {
        $approval = ContentVersionApproval::query()
            ->where('content_version_id', $versionId)
            ->where('decision', $decision->value)
            ->where('content_hash', $contentHash)
            ->latest('reviewed_at')
            ->first();

        return $approval ? $this->toRecord($approval) : null;
    }

    private function toRecord(ContentVersionApproval $approval): ContentApprovalRecord
    {
        return new ContentApprovalRecord(
            id: (int) $approval->getKey(),
            versionId: (int) $approval->content_version_id,
            reviewer: $approval->reviewer,
            decision: $approval->decision,
            contentHash: $approval->content_hash,
            comment: $approval->comment,
            reviewedAt: $approval->reviewed_at?->toISOString(),
        );
    }
}

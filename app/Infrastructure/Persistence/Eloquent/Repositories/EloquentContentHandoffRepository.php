<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Content\Records\ContentHandoffRecord;
use App\Domain\Repositories\ContentHandoffRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\ContentHandoffRecord as EloquentHandoffRecord;

final class EloquentContentHandoffRepository implements ContentHandoffRepositoryInterface
{
    public function findForVersion(int $versionId): ?ContentHandoffRecord
    {
        $record = EloquentHandoffRecord::query()
            ->where('content_version_id', $versionId)
            ->first();

        return $record ? $this->toRecord($record) : null;
    }

    public function create(array $attributes): ContentHandoffRecord
    {
        return $this->toRecord(EloquentHandoffRecord::query()->create($attributes));
    }

    private function toRecord(EloquentHandoffRecord $record): ContentHandoffRecord
    {
        return new ContentHandoffRecord(
            id: (int) $record->getKey(),
            versionId: (int) $record->content_version_id,
            approvalId: (int) $record->content_version_approval_id,
            idempotencyKey: $record->idempotency_key,
            payload: $record->payload ?? [],
            status: $record->status,
            handedOffAt: $record->handed_off_at?->toISOString(),
        );
    }
}

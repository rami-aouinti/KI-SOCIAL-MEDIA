<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Content\Records\ContentBriefingRecord;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ContentBriefing;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;

final class EloquentContentVersionRepository implements ContentVersionRepositoryInterface
{
    public function lockForWorkflow(int $versionId, bool $withReadinessRelations = false): ContentVersionRecord
    {
        $query = ContentVersion::query();

        if ($withReadinessRelations) {
            $query->with('briefing.contentIdea.workAllocations');
        }

        return $this->toRecord($query->lockForUpdate()->findOrFail($versionId), $withReadinessRelations);
    }

    public function lockBriefingForVersionCreation(int $briefingId): ContentBriefingRecord
    {
        $briefing = ContentBriefing::query()
            ->whereKey($briefingId)
            ->lockForUpdate()
            ->firstOrFail();

        return new ContentBriefingRecord((int) $briefing->getKey());
    }

    public function createNextForBriefing(int $briefingId, array $attributes): ContentVersionRecord
    {
        $briefing = ContentBriefing::query()->findOrFail($briefingId);
        $nextVersion = ((int) $briefing->versions()->max('version_number')) + 1;
        $version = $briefing->versions()->create([
            ...$attributes,
            'version_number' => $nextVersion,
        ]);

        return $this->toRecord($version);
    }

    public function updateStatus(int $versionId, ContentVersionStatus $status): ContentVersionRecord
    {
        $version = ContentVersion::query()->findOrFail($versionId);
        $version->status = $status;
        $version->save();

        return $this->toRecord($version->refresh());
    }

    private function toRecord(ContentVersion $version, bool $withReadinessRelations = false): ContentVersionRecord
    {
        $idea = $withReadinessRelations ? $version->briefing?->contentIdea : null;

        return new ContentVersionRecord(
            id: (int) $version->getKey(),
            briefingId: (int) $version->content_briefing_id,
            versionNumber: (int) $version->version_number,
            snapshot: $version->snapshot ?? [],
            mediaReference: $version->media_reference,
            channels: $version->channels ?? [],
            scheduledAt: $version->scheduled_at?->toDateString(),
            contentHash: (string) $version->content_hash,
            status: $version->status,
            responsiblePerson: $idea?->responsible_person,
            workAllocationCount: $idea?->workAllocations->count() ?? 0,
        );
    }
}

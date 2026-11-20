<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ContentBriefing;
use App\Models\ContentVersion;
use App\Repositories\Contracts\ContentVersionRepositoryInterface;

class EloquentContentVersionRepository implements ContentVersionRepositoryInterface
{
    public function lockForWorkflow(int $versionId, bool $withReadinessRelations = false): ContentVersion
    {
        $query = ContentVersion::query();

        if ($withReadinessRelations) {
            $query->with('briefing.contentIdea.workAllocations');
        }

        return $query->lockForUpdate()->findOrFail($versionId);
    }

    public function lockBriefingForVersionCreation(int $briefingId): ContentBriefing
    {
        return ContentBriefing::query()
            ->whereKey($briefingId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function createNextForBriefing(ContentBriefing $briefing, array $attributes): ContentVersion
    {
        $nextVersion = ((int) $briefing->versions()->max('version_number')) + 1;

        return $briefing->versions()->create([
            ...$attributes,
            'version_number' => $nextVersion,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\ContentBriefing;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersion;

interface ContentVersionRepositoryInterface
{
    public function lockForWorkflow(int $versionId, bool $withReadinessRelations = false): ContentVersion;

    public function lockBriefingForVersionCreation(int $briefingId): ContentBriefing;

    /** @param array<string, mixed> $attributes */
    public function createNextForBriefing(ContentBriefing $briefing, array $attributes): ContentVersion;
}

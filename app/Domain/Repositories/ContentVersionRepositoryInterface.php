<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Content\Records\ContentBriefingRecord;
use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Shared\Enums\ContentVersionStatus;

interface ContentVersionRepositoryInterface
{
    public function lockForWorkflow(int $versionId, bool $withReadinessRelations = false): ContentVersionRecord;

    public function lockBriefingForVersionCreation(int $briefingId): ContentBriefingRecord;

    /** @param array<string, mixed> $attributes */
    public function createNextForBriefing(int $briefingId, array $attributes): ContentVersionRecord;

    public function updateStatus(int $versionId, ContentVersionStatus $status): ContentVersionRecord;
}

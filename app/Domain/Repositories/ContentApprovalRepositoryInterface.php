<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Content\Records\ContentApprovalRecord;
use App\Domain\Shared\Enums\ContentApprovalDecision;

interface ContentApprovalRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): ContentApprovalRecord;

    public function findLatestForVersionAndHash(
        int $versionId,
        ContentApprovalDecision $decision,
        string $contentHash,
    ): ?ContentApprovalRecord;
}

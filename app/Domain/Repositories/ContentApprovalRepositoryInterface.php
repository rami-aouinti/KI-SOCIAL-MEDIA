<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Enums\ContentApprovalDecision;
use App\Models\ContentVersionApproval;

interface ContentApprovalRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): ContentVersionApproval;

    public function findLatestForVersionAndHash(
        int $versionId,
        ContentApprovalDecision $decision,
        string $contentHash,
    ): ?ContentVersionApproval;
}

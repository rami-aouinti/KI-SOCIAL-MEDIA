<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Shared\Enums\ContentApprovalDecision;
use App\Infrastructure\Persistence\Eloquent\Models\ContentVersionApproval;

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

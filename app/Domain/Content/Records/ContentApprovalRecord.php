<?php

declare(strict_types=1);

namespace App\Domain\Content\Records;

use App\Domain\Shared\Enums\ContentApprovalDecision;

final readonly class ContentApprovalRecord
{
    public function __construct(
        public int $id,
        public int $versionId,
        public string $reviewer,
        public ContentApprovalDecision $decision,
        public string $contentHash,
        public ?string $comment,
        public ?string $reviewedAt,
    ) {}
}

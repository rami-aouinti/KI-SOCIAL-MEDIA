<?php

declare(strict_types=1);

namespace App\Domain\Content\Records;

use App\Domain\Shared\Enums\ContentVersionStatus;

final readonly class ContentVersionRecord
{
    /**
     * @param array<string, mixed> $snapshot
     * @param list<string> $channels
     */
    public function __construct(
        public int $id,
        public int $briefingId,
        public int $versionNumber,
        public array $snapshot,
        public ?string $mediaReference,
        public array $channels,
        public ?string $scheduledAt,
        public string $contentHash,
        public ContentVersionStatus $status,
        public ?string $responsiblePerson = null,
        public int $workAllocationCount = 0,
    ) {
    }
}

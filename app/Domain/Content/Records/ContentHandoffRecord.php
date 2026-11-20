<?php

declare(strict_types=1);

namespace App\Domain\Content\Records;

final readonly class ContentHandoffRecord
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public int $id,
        public int $versionId,
        public int $approvalId,
        public string $idempotencyKey,
        public array $payload,
        public string $status,
        public ?string $handedOffAt,
    ) {}
}

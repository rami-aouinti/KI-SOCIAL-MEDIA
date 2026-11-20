<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Content\Records\ContentVersionRecord;
use App\Domain\Shared\Enums\ContentVersionStatus;
use DomainException;

final readonly class ContentVersion
{
    public function __construct(
        public int $id,
        public int $briefingId,
        public int $versionNumber,
        public string $contentHash,
        public ContentVersionStatus $status,
    ) {
        if ($id < 1 || $briefingId < 1 || $versionNumber < 1) {
            throw new \InvalidArgumentException('A persisted content version requires valid identifiers.');
        }

        if ($contentHash === '') {
            throw new \InvalidArgumentException('A content version requires a content hash.');
        }
    }

    public static function fromRecord(ContentVersionRecord $record): self
    {
        return new self($record->id, $record->briefingId, $record->versionNumber, $record->contentHash, $record->status);
    }

    public function submitForReview(): self
    {
        $this->assertStatus(ContentVersionStatus::DRAFT, 'Only draft versions can be submitted for review.');

        return $this->withStatus(ContentVersionStatus::IN_REVIEW);
    }

    public function approve(): self
    {
        $this->assertStatus(ContentVersionStatus::IN_REVIEW, 'Only versions in review can be approved.');

        return $this->withStatus(ContentVersionStatus::APPROVED);
    }

    public function reject(): self
    {
        $this->assertStatus(ContentVersionStatus::IN_REVIEW, 'Only versions in review can be rejected.');

        return $this->withStatus(ContentVersionStatus::REJECTED);
    }

    public function handOff(): self
    {
        $this->assertStatus(ContentVersionStatus::APPROVED, 'Only approved content versions can be handed off.');

        return $this->withStatus(ContentVersionStatus::HANDED_OFF);
    }

    private function withStatus(ContentVersionStatus $status): self
    {
        return new self($this->id, $this->briefingId, $this->versionNumber, $this->contentHash, $status);
    }

    private function assertStatus(ContentVersionStatus $expected, string $message): void
    {
        if ($this->status !== $expected) {
            throw new DomainException($message);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Shared\Enums\ContentVersionStatus;
use DomainException;

/**
 * Encapsulates the allowed lifecycle transitions of a content version.
 *
 * This entity intentionally has no persistence or framework dependencies.
 */
final readonly class ContentVersionWorkflow
{
    public function __construct(
        private ContentVersionStatus $status,
    ) {
    }

    public static function fromStatus(ContentVersionStatus $status): self
    {
        return new self($status);
    }

    public function status(): ContentVersionStatus
    {
        return $this->status;
    }

    public function submitForReview(): self
    {
        $this->assertStatus(ContentVersionStatus::DRAFT, 'Only draft versions can be submitted for review.');

        return new self(ContentVersionStatus::IN_REVIEW);
    }

    public function approve(): self
    {
        $this->assertStatus(ContentVersionStatus::IN_REVIEW, 'Only versions in review can be approved.');

        return new self(ContentVersionStatus::APPROVED);
    }

    public function reject(): self
    {
        $this->assertStatus(ContentVersionStatus::IN_REVIEW, 'Only versions in review can be rejected.');

        return new self(ContentVersionStatus::REJECTED);
    }

    public function handOff(): self
    {
        $this->assertStatus(ContentVersionStatus::APPROVED, 'Only approved content versions can be handed off.');

        return new self(ContentVersionStatus::HANDED_OFF);
    }

    private function assertStatus(ContentVersionStatus $expected, string $message): void
    {
        if ($this->status !== $expected) {
            throw new DomainException($message);
        }
    }
}

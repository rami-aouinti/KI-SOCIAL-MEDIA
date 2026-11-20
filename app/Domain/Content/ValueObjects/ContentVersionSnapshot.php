<?php

declare(strict_types=1);

namespace App\Domain\Content\ValueObjects;

use JsonException;

/**
 * Immutable, canonical representation of the exact content approved by a reviewer.
 *
 * The value object is deliberately framework-independent: it contains no Eloquent
 * model, Laravel collection, request, or persistence concern.
 */
final readonly class ContentVersionSnapshot
{
    /**
     * @param array<string, mixed> $approvalPayload
     */
    public function __construct(
        private array $approvalPayload,
    ) {
        if (! isset($this->approvalPayload['snapshot']) || ! is_array($this->approvalPayload['snapshot'])) {
            throw new \InvalidArgumentException('A content version snapshot must contain a snapshot object.');
        }
    }

    /**
     * @param array<string, mixed> $approvalPayload
     */
    public static function fromApprovalPayload(array $approvalPayload): self
    {
        return new self($approvalPayload);
    }

    /**
     * @return array<string, mixed>
     */
    public function content(): array
    {
        return $this->approvalPayload['snapshot'];
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalPayload(): array
    {
        return $this->approvalPayload;
    }

    /**
     * Returns a stable fingerprint for the approved content and its delivery metadata.
     *
     * @throws JsonException
     */
    public function hash(): string
    {
        $canonicalJson = json_encode(
            $this->approvalPayload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return hash('sha256', $canonicalJson);
    }
}

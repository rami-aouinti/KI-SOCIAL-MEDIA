<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Shared\Enums\ContentBriefingStatus;
use DomainException;

final readonly class ContentBriefing
{
    public function __construct(
        public ?int $id,
        public int $contentIdeaId,
        public ContentBriefingStatus $status = ContentBriefingStatus::DRAFT,
    ) {
        if ($contentIdeaId < 1) {
            throw new \InvalidArgumentException('A briefing must belong to a content idea.');
        }
    }

    public function markReady(string $hook, string $body, string $callToAction): self
    {
        if ($this->status !== ContentBriefingStatus::DRAFT) {
            throw new DomainException('Only draft briefings can be marked ready.');
        }

        if (trim($hook) === '' || trim($body) === '' || trim($callToAction) === '') {
            throw new DomainException('A ready briefing requires a hook, body and call to action.');
        }

        return new self($this->id, $this->contentIdeaId, ContentBriefingStatus::READY);
    }
}

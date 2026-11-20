<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Shared\Enums\ContentIdeaPriority;
use App\Domain\Shared\Enums\ContentIdeaStatus;
use DomainException;

final readonly class ContentIdea
{
    public function __construct(
        public ?int $id,
        public string $title,
        public ContentIdeaPriority $priority,
        public ContentIdeaStatus $status = ContentIdeaStatus::PLANNED,
    ) {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('A content idea must have a title.');
        }
    }

    public function startProduction(): self
    {
        if ($this->status !== ContentIdeaStatus::PLANNED) {
            throw new DomainException('Only planned ideas can enter production.');
        }

        return new self($this->id, $this->title, $this->priority, ContentIdeaStatus::IN_PRODUCTION);
    }

    public function complete(): self
    {
        if ($this->status !== ContentIdeaStatus::IN_PRODUCTION) {
            throw new DomainException('Only ideas in production can be completed.');
        }

        return new self($this->id, $this->title, $this->priority, ContentIdeaStatus::COMPLETED);
    }

    public function cancel(): self
    {
        if ($this->status === ContentIdeaStatus::COMPLETED || $this->status === ContentIdeaStatus::CANCELLED) {
            throw new DomainException('Completed or cancelled ideas cannot be cancelled.');
        }

        return new self($this->id, $this->title, $this->priority, ContentIdeaStatus::CANCELLED);
    }
}

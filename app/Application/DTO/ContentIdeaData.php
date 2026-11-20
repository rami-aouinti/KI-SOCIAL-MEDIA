<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ContentIdeaData
{
    /** @param list<string> $channels @param list<WorkAllocationData> $allocations */
    public function __construct(
        public string $title,
        public string $topic,
        public string $targetAudience,
        public array $channels,
        public string $format,
        public string $priorityType,
        public string $selectionReason,
        public string $responsiblePerson,
        public string $plannedAt,
        public array $allocations,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['title'],
            $data['topic'],
            $data['target_audience'],
            array_values($data['channels']),
            $data['format'],
            $data['priority_type'],
            $data['selection_reason'],
            $data['responsible_person'],
            $data['planned_at'],
            array_map(
                static fn (array $allocation): WorkAllocationData => WorkAllocationData::fromArray($allocation),
                $data['allocations'],
            ),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'topic' => $this->topic,
            'target_audience' => $this->targetAudience,
            'channels' => $this->channels,
            'format' => $this->format,
            'priority_type' => $this->priorityType,
            'selection_reason' => $this->selectionReason,
            'responsible_person' => $this->responsiblePerson,
            'planned_at' => $this->plannedAt,
            'allocations' => array_map(
                static fn (WorkAllocationData $allocation): array => $allocation->toArray(),
                $this->allocations,
            ),
        ];
    }

    public function estimatedMinutes(): int
    {
        return array_sum(array_map(
            static fn (WorkAllocationData $allocation): int => $allocation->estimatedMinutes,
            $this->allocations,
        ));
    }
}

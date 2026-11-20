<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class WorkAllocationData
{
    public function __construct(
        public string $personKey,
        public string $workType,
        public int $estimatedMinutes,
    ) {
    }

    /** @param array{person_key: string, work_type: string, estimated_minutes: int} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['person_key'], $data['work_type'], (int) $data['estimated_minutes']);
    }

    /** @return array{person_key: string, work_type: string, estimated_minutes: int} */
    public function toArray(): array
    {
        return [
            'person_key' => $this->personKey,
            'work_type' => $this->workType,
            'estimated_minutes' => $this->estimatedMinutes,
        ];
    }
}

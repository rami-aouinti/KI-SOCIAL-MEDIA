<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class InspirationData
{
    /** @param array<string, mixed>|null $metrics */
    public function __construct(
        public string $channel,
        public string $sourceId,
        public string $description,
        public ?array $metrics = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self($data['channel'], $data['source_id'], $data['description'], $data['metrics'] ?? null);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'source_id' => $this->sourceId,
            'description' => $this->description,
            'metrics' => $this->metrics,
        ];
    }
}

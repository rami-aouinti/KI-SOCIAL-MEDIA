<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ContentVersionData
{
    /** @param array<string, array<string, mixed>> $channelVariants @param list<string> $channels @param array<string, mixed>|null $generation */
    public function __construct(
        public string $hook,
        public string $body,
        public array $channelVariants,
        public ?string $mediaReference,
        public array $channels,
        public string $scheduledAt,
        public ?array $generation = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromValidatedArray(array $data): self
    {
        return new self(
            $data['hook'],
            $data['body'],
            $data['channel_variants'],
            $data['media_reference'] ?? null,
            array_values(array_unique($data['channels'])),
            $data['scheduled_at'],
            $data['generation'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $snapshot = [
            'hook' => $this->hook,
            'body' => $this->body,
            'channel_variants' => $this->channelVariants,
        ];

        if ($this->generation !== null) {
            $snapshot['generation'] = $this->generation;
        }

        return $snapshot;
    }

    /** @return array<string, mixed> */
    public function approvalPayload(): array
    {
        return [
            'snapshot' => $this->snapshot(),
            'media_reference' => $this->mediaReference,
            'channels' => $this->channels,
            'scheduled_at' => $this->scheduledAt,
        ];
    }
}

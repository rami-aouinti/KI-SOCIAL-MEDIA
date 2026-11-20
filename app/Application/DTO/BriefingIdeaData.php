<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class BriefingIdeaData
{
    /** @param list<string> $channels */
    public function __construct(
        public int $id,
        public string $title,
        public string $topic,
        public string $targetAudience,
        public array $channels,
        public string $plannedAt,
        public ?string $seasonalOccasion,
        public string $tone,
    ) {}
}

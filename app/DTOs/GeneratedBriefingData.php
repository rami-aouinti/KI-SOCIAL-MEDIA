<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class GeneratedBriefingData
{
    /** @param list<string> $onScreenText @param array<string, array<string, mixed>> $channelVariants */
    public function __construct(
        public string $hook,
        public string $body,
        public string $filmingPlan,
        public array $onScreenText,
        public string $coverIdea,
        public string $ctaText,
        public string $appointmentUrl,
        public array $channelVariants,
        public string $mediaReference,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromValidatedArray(array $data): self
    {
        return new self(
            $data['hook'],
            $data['body'],
            $data['filming_plan'],
            $data['on_screen_text'],
            $data['cover_idea'],
            $data['cta_text'],
            $data['appointment_url'],
            $data['channel_variants'],
            $data['media_reference'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'hook' => $this->hook,
            'body' => $this->body,
            'filming_plan' => $this->filmingPlan,
            'on_screen_text' => $this->onScreenText,
            'cover_idea' => $this->coverIdea,
            'cta_text' => $this->ctaText,
            'appointment_url' => $this->appointmentUrl,
            'channel_variants' => $this->channelVariants,
            'media_reference' => $this->mediaReference,
        ];
    }
}

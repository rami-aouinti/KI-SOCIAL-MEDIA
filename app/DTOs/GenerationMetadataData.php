<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class GenerationMetadataData
{
    public function __construct(
        public string $provider,
        public bool $simulated,
        public ?string $model,
    ) {
    }

    /** @return array{provider: string, simulated: bool, model: string|null} */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'simulated' => $this->simulated,
            'model' => $this->model,
        ];
    }
}

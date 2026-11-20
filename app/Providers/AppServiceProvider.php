<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AiContentGeneratorInterface;
use App\Services\MockAiContentGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        // Swap this binding for an HTTP/LLM adapter when a real provider is added.
        // The application workflow and its business rules remain unchanged.
        $this->app->bind(AiContentGeneratorInterface::class, MockAiContentGenerator::class);
    }

    public function boot(): void
    {
        //
    }
}

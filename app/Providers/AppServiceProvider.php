<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AiContentGeneratorInterface;
use App\Repositories\Contracts\ContentApprovalRepositoryInterface;
use App\Repositories\Contracts\ContentHandoffRepositoryInterface;
use App\Repositories\Contracts\ContentVersionRepositoryInterface;
use App\Repositories\Eloquent\EloquentContentApprovalRepository;
use App\Repositories\Eloquent\EloquentContentHandoffRepository;
use App\Repositories\Eloquent\EloquentContentVersionRepository;
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

        $this->app->bind(
            ContentVersionRepositoryInterface::class,
            EloquentContentVersionRepository::class,
        );
        $this->app->bind(
            ContentApprovalRepositoryInterface::class,
            EloquentContentApprovalRepository::class,
        );
        $this->app->bind(
            ContentHandoffRepositoryInterface::class,
            EloquentContentHandoffRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Content\AI\AiContentGeneratorInterface;
use App\Infrastructure\Events\ContentApproved;
use App\Infrastructure\Events\ContentHandedOff;
use App\Infrastructure\Events\ContentRejected;
use App\Infrastructure\Queue\Listeners\QueueWeeklyReportRefresh;
use Illuminate\Support\Facades\Event;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;
use App\Domain\Repositories\ContentHandoffRepositoryInterface;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentApprovalRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentHandoffRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentVersionRepository;
use App\Infrastructure\AI\Mock\MockAiContentGenerator;
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
            \App\Repositories\Contracts\ContentVersionRepositoryInterface::class,
            EloquentContentVersionRepository::class,
        );
        $this->app->bind(
            ContentApprovalRepositoryInterface::class,
            EloquentContentApprovalRepository::class,
        );
        $this->app->bind(
            \App\Repositories\Contracts\ContentApprovalRepositoryInterface::class,
            EloquentContentApprovalRepository::class,
        );
        $this->app->bind(
            ContentHandoffRepositoryInterface::class,
            EloquentContentHandoffRepository::class,
        );
        $this->app->bind(
            \App\Repositories\Contracts\ContentHandoffRepositoryInterface::class,
            EloquentContentHandoffRepository::class,
        );
    }

    public function boot(): void
    {
        // Load transitional aliases before legacy type declarations and queue assertions use them.
        foreach ([
            \App\Models\ContentBriefing::class,
            \App\Models\ContentHandoffRecord::class,
            \App\Models\ContentIdea::class,
            \App\Models\ContentVersion::class,
            \App\Models\ContentVersionApproval::class,
            \App\Models\InspirationReference::class,
            \App\Models\User::class,
            \App\Models\WeeklyPlan::class,
            \App\Models\WorkAllocation::class,
            \App\Jobs\RefreshWeeklyReportForContentVersion::class,
        ] as $legacyClass) {
            class_exists($legacyClass);
        }

        Event::listen(ContentApproved::class, QueueWeeklyReportRefresh::class);
        Event::listen(ContentRejected::class, QueueWeeklyReportRefresh::class);
        Event::listen(ContentHandedOff::class, QueueWeeklyReportRefresh::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Contracts\WeeklyPlanWriterInterface;
use App\Domain\Content\AI\AiContentGeneratorInterface;
use App\Domain\Events\WorkflowEventPublisherInterface;
use App\Domain\Repositories\ContentApprovalRepositoryInterface;
use App\Domain\Repositories\ContentHandoffRepositoryInterface;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Transactions\TransactionManagerInterface;
use App\Infrastructure\AI\Mock\MockAiContentGenerator;
use App\Infrastructure\Events\LaravelWorkflowEventPublisher;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentApprovalRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentHandoffRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentVersionRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentWeeklyPlanWriter;
use App\Infrastructure\Queue\Listeners\QueueWeeklyReportRefresh;
use App\Infrastructure\Transactions\LaravelTransactionManager;
use App\Transport\Events\ContentApproved;
use App\Transport\Events\ContentHandedOff;
use App\Transport\Events\ContentRejected;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiContentGeneratorInterface::class, MockAiContentGenerator::class);
        $this->app->bind(WeeklyPlanWriterInterface::class, EloquentWeeklyPlanWriter::class);
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);
        $this->app->bind(WorkflowEventPublisherInterface::class, LaravelWorkflowEventPublisher::class);
        $this->app->bind(ContentVersionRepositoryInterface::class, EloquentContentVersionRepository::class);
        $this->app->bind(ContentApprovalRepositoryInterface::class, EloquentContentApprovalRepository::class);
        $this->app->bind(ContentHandoffRepositoryInterface::class, EloquentContentHandoffRepository::class);
    }

    public function boot(): void
    {
        Event::listen(ContentApproved::class, QueueWeeklyReportRefresh::class);
        Event::listen(ContentRejected::class, QueueWeeklyReportRefresh::class);
        Event::listen(ContentHandedOff::class, QueueWeeklyReportRefresh::class);
    }
}

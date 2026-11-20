<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Automation\QueuedN8nAutomationEventPublisher;
use App\Infrastructure\Queue\Jobs\DispatchN8nWebhook;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class QueuedN8nAutomationEventPublisherTest extends TestCase
{
    public function test_disabled_integration_does_not_enqueue_events(): void
    {
        config(['services.n8n.enabled' => false]);
        Queue::fake();

        app(QueuedN8nAutomationEventPublisher::class)->publish('content.version.approved', [
            'content_version_id' => 12,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_enabled_integration_queues_the_event_for_async_delivery(): void
    {
        config(['services.n8n.enabled' => true]);
        Queue::fake();

        app(QueuedN8nAutomationEventPublisher::class)->publish('content.version.approved', [
            'content_version_id' => 12,
        ]);

        Queue::assertPushed(DispatchN8nWebhook::class);
    }
}

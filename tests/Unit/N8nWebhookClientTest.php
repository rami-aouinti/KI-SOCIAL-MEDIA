<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Automation\N8nWebhookClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class N8nWebhookClientTest extends TestCase
{
    public function test_disabled_integration_does_not_make_http_requests(): void
    {
        config(['services.n8n.enabled' => false]);
        Http::fake();

        $result = app(N8nWebhookClient::class)->send('content.created', ['id' => 42]);

        $this->assertFalse($result['accepted']);
        $this->assertNull($result['status']);
        Http::assertNothingSent();
    }

    public function test_enabled_integration_sends_the_event_with_shared_secret_and_id(): void
    {
        config([
            'services.n8n.enabled' => true,
            'services.n8n.webhook_url' => 'http://n8n:5678/webhook/social-media-events',
            'services.n8n.webhook_secret' => str_repeat('s', 40),
            'services.n8n.timeout' => 5,
        ]);
        Http::fake(['http://n8n:5678/*' => Http::response(['accepted' => true], 202)]);

        $result = app(N8nWebhookClient::class)->send(
            'content.version.submitted_for_review',
            ['content_version_id' => 17],
            'event-test-123',
        );

        $this->assertTrue($result['accepted']);
        $this->assertSame(202, $result['status']);
        $this->assertSame('event-test-123', $result['event_id']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://n8n:5678/webhook/social-media-events'
                && $request->hasHeader('X-Automation-Secret', str_repeat('s', 40))
                && $request->hasHeader('X-Automation-Event-Id', 'event-test-123')
                && $request['event_type'] === 'content.version.submitted_for_review'
                && $request['data']['content_version_id'] === 17;
        });
    }

    public function test_enabled_integration_rejects_a_short_webhook_secret(): void
    {
        config([
            'services.n8n.enabled' => true,
            'services.n8n.webhook_url' => 'http://n8n:5678/webhook/social-media-events',
            'services.n8n.webhook_secret' => 'too-short',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('at least 32 characters');

        app(N8nWebhookClient::class)->send('integration.test', []);
    }
}

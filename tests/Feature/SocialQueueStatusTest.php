<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialQueueStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_status_command_returns_a_healthy_json_response(): void
    {
        config(['queue.default' => 'database']);

        $this->artisan('social:queue-status', ['--json' => true])
            ->expectsOutputToContain('"status": "ok"')
            ->assertExitCode(0);
    }

    public function test_queue_status_accepts_an_explicit_queue_name(): void
    {
        config(['queue.default' => 'database']);

        $this->artisan('social:queue-status', ['--queue' => 'publishing', '--json' => true])
            ->expectsOutputToContain('"status": "ok"')
            ->assertExitCode(0);
    }
}

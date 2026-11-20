<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SocialQueueStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_status_reports_database_queue_metrics_as_json(): void
    {
        config(['queue.default' => 'database']);

        $createdAt = Carbon::parse('2026-11-20 10:00:00')->timestamp;

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $createdAt,
            'created_at' => $createdAt,
        ]);

        $this->assertDatabaseHas('jobs', ['queue' => 'default']);

        $this->artisan('social:queue-status', ['--json' => true])
            ->expectsOutputToContain('"status": "ok"')
            ->expectsOutputToContain('"queue": "default"')
            ->expectsOutputToContain('"pending_jobs":')
            ->expectsOutputToContain('"failed_jobs":')
            ->assertExitCode(0);
    }

    public function test_queue_status_can_be_invoked_for_an_explicit_queue(): void
    {
        config(['queue.default' => 'database']);

        $this->artisan('social:queue-status', ['--queue' => 'publishing', '--json' => true])
            ->expectsOutputToContain('"status": "ok"')
            ->expectsOutputToContain('"queue": "publishing"')
            ->expectsOutputToContain('"pending_jobs":')
            ->assertExitCode(0);
    }
}

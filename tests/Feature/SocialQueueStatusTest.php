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

    public function test_queue_status_reports_pending_and_failed_jobs_for_the_selected_queue(): void
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

        DB::table('jobs')->insert([
            'queue' => 'emails',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $createdAt,
            'created_at' => $createdAt,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => 'queue-status-failed-job',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $this->artisan('social:queue-status', ['--json' => true])
            ->expectsOutputToContain('"pending_jobs": 1')
            ->expectsOutputToContain('"failed_jobs": 1')
            ->expectsOutputToContain('"queue": "default"')
            ->assertExitCode(0);
    }

    public function test_queue_status_can_filter_an_explicit_queue(): void
    {
        config(['queue.default' => 'database']);

        $this->artisan('social:queue-status', ['--queue' => 'publishing', '--json' => true])
            ->expectsOutputToContain('"queue": "publishing"')
            ->expectsOutputToContain('"pending_jobs": 0')
            ->assertExitCode(0);
    }
}

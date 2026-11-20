<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_reports_all_required_dependencies_as_healthy(): void
    {
        Storage::fake('local');

        $this->artisan('social:health-check')
            ->expectsOutputToContain('All backend health checks passed.')
            ->assertExitCode(0);
    }

    public function test_health_check_can_emit_machine_readable_json(): void
    {
        Storage::fake('local');

        $this->artisan('social:health-check', ['--json' => true])
            ->expectsOutputToContain('"status": "ok"')
            ->assertExitCode(0);
    }
}

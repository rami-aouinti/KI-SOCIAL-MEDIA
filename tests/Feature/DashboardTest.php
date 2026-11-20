<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_an_empty_state_without_a_weekly_plan(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('KI Social Media')
            ->assertSee('No demo plan found');
    }

    public function test_dashboard_renders_the_demo_plan_without_exposing_eloquent_models_to_the_view(): void
    {
        $this->seed(DemoContentSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Editorial plan')
            ->assertSee('Team capacity');
    }
}

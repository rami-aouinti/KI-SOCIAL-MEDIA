<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Services\WeeklyPlanGenerator;
use App\Domain\Content\AI\AiContentGeneratorInterface;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentVersionRepository;
use App\Transport\Http\Controllers\DashboardController;
use Tests\TestCase;

class ArchitectureLayersTest extends TestCase
{
    public function test_application_domain_infrastructure_and_transport_classes_are_autoloadable(): void
    {
        $this->assertTrue(class_exists(WeeklyPlanGenerator::class));
        $this->assertTrue(interface_exists(AiContentGeneratorInterface::class));
        $this->assertTrue(interface_exists(ContentVersionRepositoryInterface::class));
        $this->assertTrue(enum_exists(ContentVersionStatus::class));
        $this->assertTrue(class_exists(WeeklyPlan::class));
        $this->assertTrue(class_exists(EloquentContentVersionRepository::class));
        $this->assertTrue(class_exists(DashboardController::class));
    }

    public function test_legacy_namespaces_remain_available_during_the_migration(): void
    {
        $this->assertTrue(class_exists(\App\Services\WeeklyPlanGenerator::class));
        $this->assertTrue(class_exists(\App\Models\WeeklyPlan::class));
        $this->assertTrue(enum_exists(\App\Enums\ContentVersionStatus::class));
        $this->assertTrue(interface_exists(\App\Repositories\Contracts\ContentVersionRepositoryInterface::class));
    }
}

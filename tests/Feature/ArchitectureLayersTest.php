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
    public function test_app_root_contains_only_the_four_architecture_layers(): void
    {
        $directories = array_values(array_filter(
            scandir(app_path()),
            static fn (string $entry): bool => $entry !== '.' && $entry !== '..' && is_dir(app_path($entry)),
        ));

        sort($directories);

        $this->assertSame(['Application', 'Domain', 'Infrastructure', 'Transport'], $directories);
    }

    public function test_classes_from_each_architecture_layer_are_autoloadable(): void
    {
        $this->assertTrue(class_exists(WeeklyPlanGenerator::class));
        $this->assertTrue(interface_exists(AiContentGeneratorInterface::class));
        $this->assertTrue(interface_exists(ContentVersionRepositoryInterface::class));
        $this->assertTrue(enum_exists(ContentVersionStatus::class));
        $this->assertTrue(class_exists(WeeklyPlan::class));
        $this->assertTrue(class_exists(EloquentContentVersionRepository::class));
        $this->assertTrue(class_exists(DashboardController::class));
    }
}

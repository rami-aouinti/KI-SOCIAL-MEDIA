<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Contracts\ContentBriefingWriterInterface;
use App\Application\Contracts\WeeklyPlanWriterInterface;
use App\Application\Contracts\WeeklyReportDataSourceInterface;
use App\Application\Contracts\WeeklyReportStorageInterface;
use App\Application\DTO\WeeklyReportData;
use App\Application\Services\WeeklyPlanGenerator;
use App\Domain\Content\AI\AiContentGeneratorInterface;
use App\Domain\Content\Entities\ContentBriefing as DomainContentBriefing;
use App\Domain\Content\Entities\ContentIdea as DomainContentIdea;
use App\Domain\Content\Entities\ContentVersion as DomainContentVersion;
use App\Domain\Planning\Entities\WeeklyPlan as DomainWeeklyPlan;
use App\Domain\Repositories\ContentVersionRepositoryInterface;
use App\Domain\Shared\Enums\ContentVersionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentBriefingWriter;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentContentVersionRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentWeeklyPlanWriter;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentWeeklyReportDataSource;
use App\Infrastructure\Storage\LaravelWeeklyReportStorage;
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

    public function test_domain_has_no_framework_or_persistence_dependencies(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path('Domain'), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            $this->assertIsString($source);
            $this->assertStringNotContainsString('Illuminate\\', $source, $file->getPathname());
            $this->assertStringNotContainsString('App\\Infrastructure\\', $source, $file->getPathname());
            $this->assertStringNotContainsString('Eloquent\\Models', $source, $file->getPathname());
        }
    }

    public function test_classes_from_each_architecture_layer_are_autoloadable(): void
    {
        $this->assertTrue(class_exists(WeeklyPlanGenerator::class));
        $this->assertTrue(interface_exists(AiContentGeneratorInterface::class));
        $this->assertTrue(class_exists(DomainContentBriefing::class));
        $this->assertTrue(class_exists(DomainContentIdea::class));
        $this->assertTrue(class_exists(DomainContentVersion::class));
        $this->assertTrue(class_exists(DomainWeeklyPlan::class));
        $this->assertTrue(interface_exists(ContentVersionRepositoryInterface::class));
        $this->assertTrue(interface_exists(WeeklyPlanWriterInterface::class));
        $this->assertTrue(interface_exists(ContentBriefingWriterInterface::class));
        $this->assertTrue(interface_exists(WeeklyReportDataSourceInterface::class));
        $this->assertTrue(interface_exists(WeeklyReportStorageInterface::class));
        $this->assertTrue(class_exists(WeeklyReportData::class));
        $this->assertTrue(enum_exists(ContentVersionStatus::class));
        $this->assertTrue(class_exists(WeeklyPlan::class));
        $this->assertTrue(class_exists(EloquentContentVersionRepository::class));
        $this->assertTrue(class_exists(EloquentWeeklyPlanWriter::class));
        $this->assertTrue(class_exists(EloquentContentBriefingWriter::class));
        $this->assertTrue(class_exists(EloquentWeeklyReportDataSource::class));
        $this->assertTrue(class_exists(LaravelWeeklyReportStorage::class));
        $this->assertTrue(class_exists(DashboardController::class));
    }
}

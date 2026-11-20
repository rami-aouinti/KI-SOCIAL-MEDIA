<?php

declare(strict_types=1);

namespace App\Transport\Console\Commands;

use App\Application\Services\WeeklyReportGenerator;
use App\Infrastructure\Persistence\Eloquent\Models\WeeklyPlan;
use Illuminate\Console\Command;

class GenerateWeeklyReport extends Command
{
    protected $signature = 'weekly-report:generate {plan? : Weekly plan ID}';

    protected $description = 'Generate a TXT report from a persisted weekly plan.';

    public function handle(WeeklyReportGenerator $generator): int
    {
        $plan = $this->argument('plan')
            ? WeeklyPlan::query()->find($this->argument('plan'))
            : WeeklyPlan::query()->latest('week_start')->first();

        if (! $plan) {
            $this->error('No weekly plan found. Run the demo seeder first.');

            return self::FAILURE;
        }

        $path = $generator->store($plan);
        $this->info('Weekly report generated: storage/app/private/'.$path);

        return self::SUCCESS;
    }
}

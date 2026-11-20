<?php

declare(strict_types=1);

namespace App\Transport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SocialQueueStatus extends Command
{
    protected $signature = 'social:queue-status
        {--queue= : Queue name (defaults to the configured database queue)}
        {--json : Print machine-readable JSON output}';

    protected $description = 'Inspect pending and failed jobs in the database queue.';

    public function handle(): int
    {
        $connection = (string) config('queue.default');
        $queue = (string) ($this->option('queue')
            ?: config('queue.connections.'.$connection.'.queue', 'default'));

        if ($connection !== 'database') {
            return $this->display([
                'status' => 'not_applicable',
                'connection' => $connection,
                'queue' => $queue,
                'pending_jobs' => null,
                'oldest_pending_at' => null,
                'failed_jobs' => null,
                'message' => 'Queue metrics are currently implemented for the database driver.',
                'checked_at' => now()->toIso8601String(),
            ], self::SUCCESS);
        }

        if (Schema::hasTable('jobs') === false || Schema::hasTable('failed_jobs') === false) {
            return $this->display([
                'status' => 'degraded',
                'connection' => $connection,
                'queue' => $queue,
                'pending_jobs' => null,
                'oldest_pending_at' => null,
                'failed_jobs' => null,
                'message' => 'Queue tables are missing. Run database migrations.',
                'checked_at' => now()->toIso8601String(),
            ], self::FAILURE);
        }

        $pendingQuery = DB::table('jobs')->where('queue', $queue);
        $pendingJobs = (clone $pendingQuery)->count();
        $oldestCreatedAt = (clone $pendingQuery)->min('created_at');

        $failedJobs = DB::table('failed_jobs')
            ->where('queue', $queue)
            ->count();

        return $this->display([
            'status' => 'ok',
            'connection' => $connection,
            'queue' => $queue,
            'pending_jobs' => $pendingJobs,
            'oldest_pending_at' => $oldestCreatedAt === null
                ? null
                : Carbon::createFromTimestamp((int) $oldestCreatedAt)->toIso8601String(),
            'failed_jobs' => $failedJobs,
            'checked_at' => now()->toIso8601String(),
        ], self::SUCCESS);
    }

    /**
     * @param array<string, mixed>  $result
     */
    private function display(array $result, int $exitCode): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $exitCode;
        }

        if ($result['status'] === 'ok') {
            $this->components->info(sprintf(
                'Queue "%s": %d pending, %d failed.',
                $result['queue'],
                $result['pending_jobs'],
                $result['failed_jobs'],
            ));

            if ($result['oldest_pending_at'] !== null) {
                $this->line('Oldest pending job: '.$result['oldest_pending_at']);
            }
        } elseif ($result['status'] === 'not_applicable') {
            $this->components->info((string) $result['message']);
        } else {
            $this->components->error((string) $result['message']);
        }

        return $exitCode;
    }
}

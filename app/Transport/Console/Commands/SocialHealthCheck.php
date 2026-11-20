<?php

declare(strict_types=1);

namespace App\Transport\Console\Commands;

use App\Domain\Content\AI\AiContentGeneratorInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SocialHealthCheck extends Command
{
    protected $signature = 'social:health-check {--json : Print machine-readable JSON output}';

    protected $description = 'Check the backend dependencies required by social-media workflows.';

    /**
     * @var array<string, string>
     */
    private array $checks = [];

    public function handle(): int
    {
        $this->check('database', fn (): bool => DB::connection()->getPdo() !== null);

        $this->check('cache', function (): bool {
            $key = 'social-health-check:'.bin2hex(random_bytes(8));
            $cache = Cache::store();

            try {
                $cache->put($key, 'ok', 10);

                return $cache->get($key) === 'ok';
            } finally {
                $cache->forget($key);
            }
        });

        $this->check('storage', function (): bool {
            $path = 'health-check/'.bin2hex(random_bytes(8)).'.tmp';
            $disk = Storage::disk('local');

            try {
                return $disk->put($path, 'ok') && $disk->get($path) === 'ok';
            } finally {
                $disk->delete($path);
            }
        });

        $this->check('ai_provider_binding', fn (): bool => app()->bound(AiContentGeneratorInterface::class));
        $this->check('queue_configuration', function (): bool {
            $connection = (string) config('queue.default');

            return $connection !== '' && is_array(config('queue.connections.'.$connection));
        });

        $failed = count(array_filter($this->checks, static fn (string $status): bool => $status !== 'ok'));
        $result = [
            'status' => $failed === 0 ? 'ok' : 'degraded',
            'checked_at' => now()->toIso8601String(),
            'checks' => $this->checks,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($this->checks as $name => $status) {
                $status === 'ok'
                    ? $this->components->info($name)
                    : $this->components->error($name.': '.$status);
            }

            $failed === 0
                ? $this->components->info('All backend health checks passed.')
                : $this->components->error($failed.' backend health check(s) failed.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(string $name, callable $probe): void
    {
        try {
            $this->checks[$name] = $probe() ? 'ok' : 'failed';
        } catch (Throwable $throwable) {
            report($throwable);
            $this->checks[$name] = 'error';
        }
    }
}

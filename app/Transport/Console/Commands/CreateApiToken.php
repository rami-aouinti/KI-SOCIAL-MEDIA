<?php

declare(strict_types=1);

namespace App\Transport\Console\Commands;

use App\Infrastructure\Persistence\Eloquent\Models\ApiToken;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class CreateApiToken extends Command
{
    protected $signature = 'social:api-token:create
        {email : Existing workspace user email}
        {--name=CLI token : Human-readable token name}
        {--abilities=plans:read,content:read : Comma-separated abilities}
        {--days=90 : Token lifetime in days (1-365)}';

    protected $description = 'Create a hashed, expiring bearer token for the Social Media API';

    private const ABILITIES = [
        'plans:read',
        'plans:write',
        'content:read',
        'content:write',
        'content:approve',
    ];

    public function handle(): int
    {
        $user = User::query()->where('email', strtolower(trim((string) $this->argument('email'))))->first();

        if ($user === null) {
            $this->error('No workspace user exists with that email address.');

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        $abilities = array_values(array_unique(array_filter(array_map(
            'trim',
            explode(',', (string) $this->option('abilities')),
        ))));

        if ($name === '' || mb_strlen($name) > 100) {
            $this->error('The token name must contain between 1 and 100 characters.');

            return self::FAILURE;
        }

        if ($days === false || $days < 1 || $days > 365) {
            $this->error('Token lifetime must be between 1 and 365 days.');

            return self::FAILURE;
        }

        if ($abilities === [] || array_diff($abilities, self::ABILITIES) !== []) {
            $this->error('Invalid abilities. Allowed: '.implode(', ', self::ABILITIES));

            return self::FAILURE;
        }

        $plainTextToken = 'ksm_'.Str::random(64);

        ApiToken::query()->create([
            'user_id' => $user->getKey(),
            'name' => $name,
            'token_hash' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => now()->addDays($days),
        ]);

        $this->newLine();
        $this->info('API token created. Copy it now; it will not be shown again.');
        $this->line($plainTextToken);
        $this->warn('Store it in a secret manager. Never commit it or put it in frontend source code.');

        return self::SUCCESS;
    }
}

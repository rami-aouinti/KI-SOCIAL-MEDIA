<?php

declare(strict_types=1);

namespace App\Transport\Console\Commands;

use App\Infrastructure\Persistence\Eloquent\Models\ApiToken;
use Illuminate\Console\Command;

final class RevokeApiToken extends Command
{
    protected $signature = 'social:api-token:revoke {id : API token database ID}';

    protected $description = 'Revoke an API bearer token';

    public function handle(): int
    {
        $id = filter_var($this->argument('id'), FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            $this->error('A valid token ID is required.');

            return self::FAILURE;
        }

        $deleted = ApiToken::query()->whereKey($id)->delete();

        if ($deleted === 0) {
            $this->error('API token not found.');

            return self::FAILURE;
        }

        $this->info("API token {$id} revoked.");

        return self::SUCCESS;
    }
}

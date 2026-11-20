<?php

declare(strict_types=1);

namespace App\Transport\Console\Commands;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Console\Command;

class CreateSocialUser extends Command
{
    protected $signature = 'social:user:create';

    protected $description = 'Create a user who can access the social media workspace';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Name'));
        $email = strtolower(trim((string) $this->ask('Email')));
        $password = (string) $this->secret('Password (minimum 12 characters)');

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A name and a valid email address are required.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('A user with this email address already exists.');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('The password must contain at least 12 characters.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->info("User {$email} created successfully.");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\ApiToken;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rejects_requests_without_a_bearer_token(): void
    {
        $this->getJson('/api/v1/weekly-plans')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_api_accepts_a_valid_token_and_returns_paginated_plans(): void
    {
        $token = $this->createToken(['plans:read']);

        $this->withToken($token)
            ->getJson('/api/v1/weekly-plans')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_api_rejects_an_invalid_or_expired_token(): void
    {
        $this->withToken('not-a-real-token')
            ->getJson('/api/v1/weekly-plans')
            ->assertUnauthorized();

        $token = $this->createToken(['plans:read'], now()->subMinute());

        $this->withToken($token)
            ->getJson('/api/v1/weekly-plans')
            ->assertUnauthorized();
    }

    public function test_api_enforces_token_abilities(): void
    {
        $token = $this->createToken(['plans:read']);

        $this->withToken($token)
            ->postJson('/api/v1/weekly-plans', [])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'insufficient_ability');
    }

    public function test_token_last_used_timestamp_is_updated(): void
    {
        $token = $this->createToken(['plans:read']);
        $this->withToken($token)->getJson('/api/v1/weekly-plans')->assertOk();

        $this->assertNotNull(ApiToken::query()->firstOrFail()->fresh()->last_used_at);
    }

    /**
     * @param list<string>          $abilities
     * @param DateTimeInterface|null $expiresAt
     */
    private function createToken(array $abilities, ?DateTimeInterface $expiresAt = null): string
    {
        User::factory()->create()->apiTokens()->create([
            'name' => 'Feature test',
            'token_hash' => hash('sha256', $plainText = 'ksm_test_'.str()->random(48)),
            'abilities' => $abilities,
            'expires_at' => $expiresAt ?? now()->addDays(30),
        ]);

        return $plainText;
    }
}

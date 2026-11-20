<?php

declare(strict_types=1);

namespace App\Domain\Content\AI;

/**
 * Provider-neutral contract for structured AI content generation.
 *
 * A real LLM adapter can implement this contract later. Business rules,
 * validation, persistence and approval must remain outside the provider.
 */
interface AiContentGeneratorInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{ideas: array<int, array<string, mixed>>}
     */
    public function generateWeeklyPlan(array $context): array;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function generateBriefing(array $context): array;
}

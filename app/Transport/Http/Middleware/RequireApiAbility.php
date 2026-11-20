<?php

declare(strict_types=1);

namespace App\Transport\Http\Middleware;

use App\Infrastructure\Persistence\Eloquent\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireApiAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $token = $request->attributes->get('api_token');

        if (! $token instanceof ApiToken || ! $token->can($ability)) {
            return new JsonResponse([
                'message' => 'This API token does not have permission to perform this action.',
                'error' => ['code' => 'insufficient_ability', 'required' => $ability],
            ], 403);
        }

        return $next($request);
    }
}

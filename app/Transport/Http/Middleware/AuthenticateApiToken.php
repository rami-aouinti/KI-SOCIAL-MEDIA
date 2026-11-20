<?php

declare(strict_types=1);

namespace App\Transport\Http\Middleware;

use App\Infrastructure\Persistence\Eloquent\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return $this->unauthenticated();
        }

        $token = ApiToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if ($token === null || $token->isExpired() || $token->user === null) {
            return $this->unauthenticated();
        }

        $request->setUserResolver(static fn () => $token->user);

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
            'error' => ['code' => 'unauthenticated'],
        ], 401);
    }
}

<?php

namespace Ticket\IdentityAccess\Infrastructure\Laravel;

use App\Models\PlatformApiToken;
use App\Models\PlatformUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Ticket\IdentityAccess\Contracts\PlatformTokenIssuer;

class LaravelPlatformTokenService implements PlatformTokenIssuer
{
    public function createToken(
        PlatformUser $user,
        string $name,
        ?array $abilities = null,
        ?\DateTimeInterface $expiresAt = null,
    ): string {
        $expiresAt ??= CarbonImmutable::now()->addMinutes(
            max(1, (int) config('ticket.token_expirations.platform_api_minutes', 60 * 24))
        );

        $plainToken = Str::random(40);
        $hashedToken = hash('sha256', $plainToken);

        $user->apiTokens()->create([
            'name' => $name,
            'token' => $hashedToken,
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return $plainToken;
    }

    public function findToken(string $plainToken): ?PlatformApiToken
    {
        $hashed = hash('sha256', $plainToken);

        $token = PlatformApiToken::query()
            ->with('platformUser')
            ->where('token', $hashed)
            ->first();

        if ($token === null || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public function revokeToken(PlatformApiToken $token): void
    {
        $token->delete();
    }

    public function revokeAllTokens(PlatformUser $user): int
    {
        return $user->apiTokens()->delete();
    }
}

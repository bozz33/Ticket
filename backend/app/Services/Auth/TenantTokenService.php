<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserApiToken;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class TenantTokenService
{
    public function createToken(User $user, string $name, ?array $abilities = null, ?\DateTimeInterface $expiresAt = null): string
    {
        $expiresAt ??= CarbonImmutable::now()->addMinutes(
            max(1, (int) config('ticket.token_expirations.tenant_api_minutes', 60 * 24 * 30))
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

    public function findToken(string $plainToken): ?UserApiToken
    {
        $hashed = hash('sha256', $plainToken);

        $token = UserApiToken::query()
            ->with('user')
            ->where('token', $hashed)
            ->first();

        if ($token === null || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public function revokeToken(UserApiToken $token): void
    {
        $token->delete();
    }

    public function revokeAllTokens(User $user): int
    {
        return $user->apiTokens()->delete();
    }
}

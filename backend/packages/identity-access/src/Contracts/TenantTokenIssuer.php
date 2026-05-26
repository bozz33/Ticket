<?php

namespace Ticket\IdentityAccess\Contracts;

use App\Models\User;
use App\Models\UserApiToken;

interface TenantTokenIssuer
{
    public function createToken(
        User $user,
        string $name,
        ?array $abilities = null,
        ?\DateTimeInterface $expiresAt = null,
    ): string;

    public function findToken(string $plainToken): ?UserApiToken;

    public function revokeToken(UserApiToken $token): void;

    public function revokeAllTokens(User $user): int;
}

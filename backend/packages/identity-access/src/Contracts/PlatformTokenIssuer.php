<?php

namespace Ticket\IdentityAccess\Contracts;

use App\Models\PlatformApiToken;
use App\Models\PlatformUser;

interface PlatformTokenIssuer
{
    public function createToken(
        PlatformUser $user,
        string $name,
        ?array $abilities = null,
        ?\DateTimeInterface $expiresAt = null,
    ): string;

    public function findToken(string $plainToken): ?PlatformApiToken;

    public function revokeToken(PlatformApiToken $token): void;

    public function revokeAllTokens(PlatformUser $user): int;
}

<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\MarketplaceSessionExchangeRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\TenantTokenService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicMarketplaceSessionController extends Controller
{
    public function __construct(private readonly TenantTokenService $tokenService) {}

    public function exchange(MarketplaceSessionExchangeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $sourceTenant = $this->findTenant((string) $validated['source_tenant']);
        $targetTenant = $this->findTenant((string) $validated['target_tenant']);
        $bearer = $this->extractBearer($request);

        if ($sourceTenant === null || $targetTenant === null) {
            return response()->json(['message' => 'Espace organisateur introuvable.'], 404);
        }

        if ($bearer === null) {
            return response()->json(['message' => 'Token d\'authentification manquant.'], 401);
        }

        $sourceBuyer = $sourceTenant->run(function () use ($bearer): ?array {
            $apiToken = $this->tokenService->findToken($bearer);

            if ($apiToken === null || $apiToken->user === null || ! $apiToken->user->is_active) {
                return null;
            }

            $apiToken->touchLastUsed();
            $user = $apiToken->user;

            return [
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => Str::lower((string) $user->email),
                'password' => $user->password,
                'phone' => $user->phone,
                'locale' => $user->locale,
                'timezone' => $user->timezone,
                'email_verified_at' => $user->email_verified_at,
                'avatar_path' => $user->avatar_path,
            ];
        });

        if ($sourceBuyer === null) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 401);
        }

        if ($sourceTenant->is($targetTenant)) {
            return response()->json([
                'data' => [
                    'token' => $bearer,
                    'user' => [
                        'name' => $sourceBuyer['name'],
                        'email' => $sourceBuyer['email'],
                    ],
                    'tenant' => $targetTenant->only(['id', 'public_id', 'name', 'slug']),
                ],
            ]);
        }

        $tokenName = (string) ($validated['token_name'] ?? 'marketplace_public');
        $targetSession = $targetTenant->run(function () use ($sourceBuyer, $tokenName): array {
            $user = User::withTrashed()->where('email', $sourceBuyer['email'])->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $sourceBuyer['name'] ?: $sourceBuyer['email'],
                    'first_name' => $sourceBuyer['first_name'],
                    'last_name' => $sourceBuyer['last_name'],
                    'username' => $this->uniqueUsername($sourceBuyer['username'], $sourceBuyer['email']),
                    'email' => $sourceBuyer['email'],
                    'password' => $sourceBuyer['password'],
                    'phone' => $sourceBuyer['phone'],
                    'locale' => $sourceBuyer['locale'] ?: config('app.locale'),
                    'timezone' => $sourceBuyer['timezone'] ?: config('app.timezone'),
                    'email_verified_at' => $sourceBuyer['email_verified_at'],
                    'avatar_path' => $sourceBuyer['avatar_path'],
                    'is_active' => true,
                ]);
            } else {
                if (method_exists($user, 'restore') && method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }

                $user->forceFill([
                    'name' => $user->name ?: ($sourceBuyer['name'] ?: $sourceBuyer['email']),
                    'first_name' => $user->first_name ?: $sourceBuyer['first_name'],
                    'last_name' => $user->last_name ?: $sourceBuyer['last_name'],
                    'phone' => $user->phone ?: $sourceBuyer['phone'],
                    'locale' => $user->locale ?: ($sourceBuyer['locale'] ?: config('app.locale')),
                    'timezone' => $user->timezone ?: ($sourceBuyer['timezone'] ?: config('app.timezone')),
                    'email_verified_at' => $user->email_verified_at ?: $sourceBuyer['email_verified_at'],
                    'is_active' => true,
                ])->save();
            }

            return [
                'token' => $this->tokenService->createToken($user, $tokenName, ['*']),
                'user' => [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ];
        });

        return response()->json([
            'data' => [
                ...$targetSession,
                'tenant' => $targetTenant->only(['id', 'public_id', 'name', 'slug']),
            ],
        ]);
    }

    private function findTenant(string $identifier): ?Tenant
    {
        return Tenant::query()
            ->where(function (Builder $query) use ($identifier): void {
                if (is_numeric($identifier)) {
                    $query->whereKey($identifier);
                }

                if (Str::isUuid($identifier)) {
                    $query->orWhere('public_id', $identifier);
                }

                $query->orWhere('slug', Str::slug($identifier));
            })
            ->first();
    }

    private function extractBearer(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with((string) $header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr((string) $header, 7));

        return blank($token) ? null : $token;
    }

    private function uniqueUsername(?string $username, string $email): string
    {
        $base = Str::slug($username ?: Str::before($email, '@'), '_') ?: 'buyer';
        $candidate = Str::lower($base);
        $suffix = 1;

        while (User::withTrashed()->where('username', $candidate)->exists()) {
            $candidate = sprintf('%s_%d', $base, $suffix);
            $suffix++;
        }

        return $candidate;
    }
}

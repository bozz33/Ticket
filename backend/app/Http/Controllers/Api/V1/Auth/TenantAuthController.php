<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TenantTokenService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantAuthController extends Controller
{
    public function __construct(
        private readonly TenantTokenService $tokenService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->where('is_active', true)
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $token = $this->tokenService->createToken(
            $user,
            $validated['token_name'] ?? 'api',
            ['*'],
        );

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return response()->json([
            'token' => $token,
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'token_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => Str::lower(Str::before($validated['email'], '@')) . '_' . Str::random(4),
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        $token = $this->tokenService->createToken(
            $user,
            $validated['token_name'] ?? 'panel_acheteur',
            ['*'],
        );

        return response()->json([
            'token' => $token,
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiToken = $request->attributes->get('tenant_api_token');

        if ($apiToken !== null) {
            $this->tokenService->revokeToken($apiToken);
        }

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('tenant_user');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => $user,
        ]);
    }
}

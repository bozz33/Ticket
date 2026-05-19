<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\PlatformLoginRequest;
use App\Models\PlatformUser;
use App\Services\Auth\PlatformTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlatformAuthController extends Controller
{
    public function __construct(private readonly PlatformTokenService $tokenService) {}

    public function login(PlatformLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = PlatformUser::query()
            ->where('email', $validated['email'])
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
            'user' => $user->only(['id', 'name', 'email', 'is_super_admin']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiToken = $request->attributes->get('platform_api_token');

        if ($apiToken !== null) {
            $this->tokenService->revokeToken($apiToken);
        }

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('platform_user');

        return response()->json(['data' => $user]);
    }
}

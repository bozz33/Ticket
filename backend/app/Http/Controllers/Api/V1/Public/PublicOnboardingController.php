<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\PublicOnboardingRegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\Tenancy\Contracts\TenantProvisioner;

class PublicOnboardingController extends Controller
{
    public function __construct(
        private readonly TenantProvisioner $tenantProvisioner,
    ) {}

    public function register(PublicOnboardingRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $slug = Str::slug($validated['org_name']);

        // Ensure slug uniqueness
        if (DB::connection('central')->table('tenants')->where('slug', $slug)->exists()) {
            return response()->json([
                'message' => 'Un organisme avec ce nom existe déjà.',
                'errors' => ['org_name' => ['Ce nom est déjà pris. Essayez un nom différent.']],
            ], 422);
        }

        $result = $this->tenantProvisioner->handle([
            'name' => $validated['org_name'],
            'slug' => $slug,
            'email' => $validated['email'],
            'country_code' => $validated['country_code'] ?? null,
            'currency_code' => $validated['currency_code'] ?? null,
            'activate' => true,
            'admin' => [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'name' => $validated['org_name'],
            ],
        ]);

        $tenant = $result['tenant'];
        $admin = $result['tenant_admin'];

        return response()->json([
            'message' => 'Votre espace organisateur a été créé avec succès.',
            'tenant' => [
                'slug' => $tenant->slug,
                'name' => $tenant->name,
                'access_url' => $admin['access_url'],
                'login_url' => $admin['login_url'],
            ],
        ], 201);
    }
}

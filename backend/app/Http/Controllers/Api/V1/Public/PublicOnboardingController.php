<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\ProvisionTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicOnboardingController extends Controller
{
    public function __construct(
        private readonly ProvisionTenant $provisionTenant,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'org_name'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email:rfc', 'max:255'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'currency_code' => ['nullable', 'string', 'size:3'],
        ]);

        $slug = Str::slug($validated['org_name']);

        // Ensure slug uniqueness
        if (DB::connection('central')->table('tenants')->where('slug', $slug)->exists()) {
            return response()->json([
                'message' => 'Un organisme avec ce nom existe déjà.',
                'errors'  => ['org_name' => ['Ce nom est déjà pris. Essayez un nom différent.']],
            ], 422);
        }

        $result = $this->provisionTenant->handle([
            'name'          => $validated['org_name'],
            'slug'          => $slug,
            'email'         => $validated['email'],
            'country_code'  => $validated['country_code'] ?? null,
            'currency_code' => $validated['currency_code'] ?? null,
            'activate'      => true,
            'admin' => [
                'email'    => $validated['email'],
                'password' => $validated['password'],
                'name'     => $validated['org_name'],
            ],
        ]);

        $tenant = $result['tenant'];
        $admin  = $result['tenant_admin'];

        return response()->json([
            'message'   => 'Votre espace organisateur a été créé avec succès.',
            'tenant'    => [
                'slug'      => $tenant->slug,
                'name'      => $tenant->name,
                'login_url' => $admin['login_url'],
            ],
        ], 201);
    }
}

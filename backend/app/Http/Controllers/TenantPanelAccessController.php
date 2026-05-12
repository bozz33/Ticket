<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantPanelAccessController extends Controller
{
    public function __invoke(
        Request $request,
        TenantContext $tenantContext,
        AuthFactory $auth,
        string $tenant,
        string $user,
    ): RedirectResponse {
        $currentTenant = $tenantContext->get();

        if ($currentTenant === null) {
            abort(404);
        }

        $tenantUser = User::query()->find((int) $user);

        if ($tenantUser === null || ! $tenantUser->is_active) {
            return redirect()->to(url(sprintf('/tenants/%s/admin/login', $currentTenant->slug)));
        }

        $guard = $auth->guard('tenant');

        $guard->logout();
        $guard->login($tenantUser);
        $request->session()->regenerate();

        return redirect()->to(url(sprintf('/tenants/%s/admin', $currentTenant->slug)));
    }
}

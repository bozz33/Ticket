<?php

namespace App\Http\Responses;

use App\Models\Tenant;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if (Filament::getCurrentPanel()?->getId() !== 'tenant') {
            return redirect()->intended(Filament::getUrl());
        }

        /** @var Request $request */
        $tenant = tenancy()->tenant;
        $routeTenant = $request->route('tenant');

        $slug = $tenant instanceof Tenant
            ? $tenant->slug
            : (is_string($routeTenant) ? $routeTenant : null);

        if (! filled($slug)) {
            return redirect()->intended(Filament::getUrl());
        }

        $fallback = url(sprintf('/tenants/%s/admin', $slug));
        $intended = session()->pull('url.intended');

        if (is_string($intended) && filled($intended)) {
            $intended = str_replace(['{tenant}', '%7Btenant%7D'], $slug, $intended);

            return redirect()->to($intended);
        }

        return redirect()->to($fallback);
    }
}

<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantFilesystemSuffix
{
    public static function for(Tenant $tenant): string
    {
        $slug = trim((string) ($tenant->slug ?? ''));

        if ($slug !== '') {
            return Str::slug($slug, '-') ?: $slug;
        }

        $name = trim((string) ($tenant->name ?? ''));

        if ($name !== '') {
            $fallback = Str::slug($name, '-');

            if ($fallback !== '') {
                return $fallback;
            }
        }

        return sprintf('%s%s', config('tenancy.filesystem.suffix_base', 'tenant'), $tenant->getTenantKey());
    }

    public static function legacyFor(Tenant $tenant): string
    {
        return sprintf('%s%s', config('tenancy.filesystem.suffix_base', 'tenant'), $tenant->getTenantKey());
    }
}

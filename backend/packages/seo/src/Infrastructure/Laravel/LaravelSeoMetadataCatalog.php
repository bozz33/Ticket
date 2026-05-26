<?php

namespace Ticket\Seo\Infrastructure\Laravel;

use App\Models\PlatformSetting;
use Illuminate\Support\Str;
use Ticket\Seo\Contracts\SeoMetadataCatalog;

class LaravelSeoMetadataCatalog implements SeoMetadataCatalog
{
    public function publicSettings(): array
    {
        return PlatformSetting::query()
            ->where('group', 'seo')
            ->where('is_public', true)
            ->orderBy('key')
            ->get(['key', 'value'])
            ->mapWithKeys(fn (PlatformSetting $setting): array => [
                (string) $setting->key => (array) ($setting->value ?? []),
            ])
            ->all();
    }

    public function forRoute(?string $routePath): array
    {
        $normalizedRoute = $this->normalizeRoute($routePath);
        $settings = $this->publicSettings();

        $routeSpecific = collect($settings)
            ->first(fn (array $value): bool => $this->normalizeRoute($value['route_path'] ?? null) === $normalizedRoute);

        return array_replace(
            (array) ($settings['seo.defaults'] ?? []),
            $routeSpecific ?? [],
        );
    }

    private function normalizeRoute(mixed $routePath): string
    {
        $route = trim((string) ($routePath ?? '/'));

        if ($route === '') {
            return '/';
        }

        return Str::startsWith($route, '/') ? $route : '/'.$route;
    }
}

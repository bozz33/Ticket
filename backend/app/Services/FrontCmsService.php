<?php

namespace App\Services;

use App\Enums\FrontMenuLocation;
use App\Enums\FrontPageStatus;
use App\Models\FrontMenu;
use App\Models\FrontMenuItem;
use App\Models\FrontPage;
use App\Models\FrontPageSection;
use Illuminate\Support\Arr;

class FrontCmsService
{
    public function publicMenus(): array
    {
        return FrontMenu::query()
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->where('is_active', true)
            ->orderBy('location')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (FrontMenu $menu): array => [
                $menu->location?->value ?? (string) $menu->getAttribute('location') => $menu->items
                    ->map(fn (FrontMenuItem $item): array => [
                        'label' => (string) $item->label,
                        'href' => (string) $item->href,
                        'target' => $item->target ?: '_self',
                        'meta' => $item->meta ?? [],
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    public function resolvePublishedPage(string $path): ?FrontPage
    {
        $normalizedPath = '/' . trim($path, '/');

        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        return FrontPage::query()
            ->with(['sections' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->where('route_path', $normalizedPath)
            ->where('is_active', true)
            ->where('status', FrontPageStatus::Published->value)
            ->first();
    }

    public function transformPage(FrontPage $page): array
    {
        return [
            'key' => (string) $page->key,
            'title' => (string) $page->title,
            'path' => (string) $page->route_path,
            'slug' => (string) $page->slug,
            'template' => (string) $page->template,
            'seo' => [
                'title' => (string) ($page->seo_title ?: $page->title),
                'description' => (string) ($page->seo_description ?: data_get($page->meta ?? [], 'summary', '')),
                'image' => $page->seo_image_url,
            ],
            'meta' => $page->meta ?? [],
            'sections' => $page->sections
                ->map(fn (FrontPageSection $section): array => [
                    'key' => (string) $section->key,
                    'type' => $section->type?->value ?? (string) $section->getAttribute('type'),
                    'title' => $section->title,
                    'eyebrow' => $section->eyebrow,
                    'body' => $section->body,
                    'image_url' => $section->image_url,
                    'primary_cta' => [
                        'label' => $section->primary_cta_label,
                        'url' => $section->primary_cta_url,
                    ],
                    'secondary_cta' => [
                        'label' => $section->secondary_cta_label,
                        'url' => $section->secondary_cta_url,
                    ],
                    'items' => Arr::wrap($section->items),
                    'settings' => $section->settings ?? [],
                ])
                ->values()
                ->all(),
        ];
    }

    public function publicPagePayload(string $path): ?array
    {
        $page = $this->resolvePublishedPage($path);

        if (! $page) {
            return null;
        }

        return $this->transformPage($page);
    }

    public function publicPagesIndex(): array
    {
        return FrontPage::query()
            ->where('is_active', true)
            ->where('status', FrontPageStatus::Published->value)
            ->orderBy('route_path')
            ->get(['key', 'title', 'route_path', 'updated_at', 'published_at'])
            ->map(fn (FrontPage $page): array => [
                'key' => (string) $page->key,
                'title' => (string) $page->title,
                'path' => (string) $page->route_path,
                'updated_at' => $page->updated_at?->toISOString(),
                'published_at' => $page->published_at?->toISOString(),
            ])
            ->all();
    }

    public function defaultMenus(): array
    {
        return [
            FrontMenuLocation::HeaderPrimary->value => [],
            FrontMenuLocation::HeaderUtility->value => [],
            FrontMenuLocation::FooterExplore->value => [],
            FrontMenuLocation::FooterPlatform->value => [],
            FrontMenuLocation::FooterBottom->value => [],
        ];
    }
}

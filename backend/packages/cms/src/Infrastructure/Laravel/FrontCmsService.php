<?php

namespace Ticket\Cms\Infrastructure\Laravel;

use App\Enums\FrontMenuLocation;
use App\Enums\FrontPageStatus;
use App\Models\FrontMenu;
use App\Models\FrontMenuItem;
use App\Models\FrontPage;
use App\Models\FrontPageSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ticket\Cms\Contracts\FrontCmsContent;

class FrontCmsService implements FrontCmsContent
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
        $normalizedPath = '/'.trim($path, '/');

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
                'image' => $this->publicAssetUrl($page->seo_image_url),
                'keywords' => data_get($page->meta ?? [], 'seo.keywords', []),
                'canonical_url' => data_get($page->meta ?? [], 'seo.canonical_url'),
                'robots_index' => data_get($page->meta ?? [], 'seo.robots_index'),
                'robots_follow' => data_get($page->meta ?? [], 'seo.robots_follow'),
                'max_image_preview' => data_get($page->meta ?? [], 'seo.max_image_preview'),
                'og_title' => data_get($page->meta ?? [], 'seo.og_title'),
                'og_description' => data_get($page->meta ?? [], 'seo.og_description'),
                'og_type' => data_get($page->meta ?? [], 'seo.og_type'),
                'og_image' => $this->publicAssetUrl(data_get($page->meta ?? [], 'seo.og_image')),
                'og_image_alt' => data_get($page->meta ?? [], 'seo.og_image_alt'),
                'twitter_title' => data_get($page->meta ?? [], 'seo.twitter_title'),
                'twitter_description' => data_get($page->meta ?? [], 'seo.twitter_description'),
                'twitter_image' => $this->publicAssetUrl(data_get($page->meta ?? [], 'seo.twitter_image')),
                'twitter_card' => data_get($page->meta ?? [], 'seo.twitter_card'),
                'structured_data_json' => data_get($page->meta ?? [], 'seo.structured_data_json'),
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
            ->where('show_in_sitemap', true)
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
            FrontMenuLocation::HeaderTopLeft->value => [],
            FrontMenuLocation::HeaderTopRight->value => [],
            FrontMenuLocation::HeaderPrimary->value => [],
            FrontMenuLocation::HeaderUtility->value => [],
            FrontMenuLocation::HeaderActions->value => [],
            FrontMenuLocation::FooterExplore->value => [],
            FrontMenuLocation::FooterPlatform->value => [],
            FrontMenuLocation::FooterBottom->value => [],
        ];
    }

    private function publicAssetUrl(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '') {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://', 'data:', '/'])) {
            return $normalized;
        }

        return Storage::disk('public')->url($normalized);
    }
}

<?php

namespace Ticket\Localization\Infrastructure\Laravel;

use App\Models\Language;
use App\Models\TranslationEntry;
use Ticket\Localization\Contracts\PublicLocalizationCatalog;

class LaravelPublicLocalizationCatalog implements PublicLocalizationCatalog
{
    public function publicLanguages(): array
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'locale', 'name', 'native_name', 'meta'])
            ->map(fn (Language $language): array => [
                'code' => (string) $language->code,
                'locale' => (string) ($language->locale ?: $language->code),
                'name' => (string) $language->name,
                'native_name' => (string) ($language->native_name ?: $language->name),
                'is_default' => (bool) data_get($language->meta ?? [], 'is_default', false),
            ])
            ->values()
            ->all();
    }

    public function publicTranslations(): array
    {
        return TranslationEntry::query()
            ->with('language:id,code,locale')
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy(fn (TranslationEntry $entry): string => (string) ($entry->language?->code ?: ''))
            ->filter(fn ($entries, string $locale): bool => $locale !== '')
            ->map(fn ($entries): array => $entries
                ->mapWithKeys(fn (TranslationEntry $entry): array => [
                    trim((string) $entry->key) => (string) $entry->value,
                ])
                ->filter(fn (string $value, string $key): bool => $key !== '' && $value !== '')
                ->all())
            ->all();
    }
}

<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Collection;

class PlatformSettingsService
{
    public function list(?string $group = null, ?bool $publicOnly = null): Collection
    {
        return PlatformSetting::query()
            ->when($group !== null && $group !== '', fn ($query) => $query->where('group', $group))
            ->when($publicOnly !== null, fn ($query) => $query->where('is_public', $publicOnly))
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function grouped(?string $group = null, ?bool $publicOnly = null): array
    {
        return $this->list($group, $publicOnly)
            ->groupBy(fn (PlatformSetting $setting) => $setting->group ?: 'general')
            ->map(fn (Collection $settings) => $settings
                ->mapWithKeys(fn (PlatformSetting $setting) => [$setting->key => $setting->value])
                ->all())
            ->all();
    }

    public function upsertMany(array $items): Collection
    {
        $settings = collect();

        foreach ($items as $item) {
            $settings->push(PlatformSetting::query()->updateOrCreate(
                ['key' => $item['key']],
                [
                    'group' => $item['group'] ?? null,
                    'value' => $item['value'] ?? null,
                    'type' => $item['type'] ?? 'json',
                    'is_public' => (bool) ($item['is_public'] ?? false),
                ],
            ));
        }

        return $settings;
    }
}

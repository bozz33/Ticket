<?php

namespace App\Services\Tenancy;

use App\Models\TenantSetting;
use Illuminate\Support\Collection;

class TenantSettingsService
{
    public function list(?string $group = null): Collection
    {
        return TenantSetting::query()
            ->when($group !== null && $group !== '', fn ($query) => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function upsertMany(array $items): Collection
    {
        $settings = collect();

        foreach ($items as $item) {
            $settings->push(TenantSetting::query()->updateOrCreate(
                ['key' => $item['key']],
                [
                    'group' => $item['group'] ?? null,
                    'value' => $item['value'] ?? null,
                    'type' => $item['type'] ?? 'json',
                    'is_encrypted' => (bool) ($item['is_encrypted'] ?? false),
                ],
            ));
        }

        return $settings;
    }
}

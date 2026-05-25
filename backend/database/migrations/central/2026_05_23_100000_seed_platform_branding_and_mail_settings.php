<?php

use App\Services\PlatformMailSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('platform_settings')) {
            return;
        }

        $connection = DB::connection('central');
        $now = now();

        $settings = [
            [
                'group' => 'branding',
                'key' => 'public_branding',
                'type' => 'json',
                'is_public' => true,
                'value' => [
                    'platform_name' => 'Ticket',
                    'tagline' => 'Public marketplace',
                    'logo_url' => null,
                    'favicon_url' => null,
                    'apple_touch_icon_url' => null,
                ],
            ],
            [
                'group' => 'mail',
                'key' => PlatformMailSettings::SETTING_KEY,
                'type' => 'json',
                'is_public' => false,
                'value' => [
                    'host' => null,
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => null,
                    'password' => null,
                    'from_address' => 'support@ticket.africa',
                    'from_name' => 'Ticket',
                ],
            ],
        ];

        foreach ($settings as $setting) {
            $existing = $connection->table('platform_settings')->where('key', $setting['key'])->first();

            $connection->table('platform_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'],
                    'value' => json_encode(array_replace(
                        (array) json_decode((string) ($existing?->value ?? '[]'), true),
                        $setting['value'],
                    ), JSON_THROW_ON_ERROR),
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('platform_settings')) {
            return;
        }

        DB::connection('central')
            ->table('platform_settings')
            ->whereIn('key', ['public_branding', PlatformMailSettings::SETTING_KEY])
            ->delete();
    }
};

<?php

namespace App\Filament\Platform\Resources\MailSettings\Pages;

use App\Filament\Platform\Resources\MailSettings\MailSettingResource;
use App\Filament\Support\Pages\ListRecordsPage;
use App\Models\PlatformSetting;
use App\Services\PlatformMailSettings;

class ListMailSettings extends ListRecordsPage
{
    protected static string $resource = MailSettingResource::class;

    public function mount(): void
    {
        PlatformSetting::query()->firstOrCreate(
            ['key' => PlatformMailSettings::SETTING_KEY],
            [
                'group' => 'mail',
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
        );

        parent::mount();
    }
}

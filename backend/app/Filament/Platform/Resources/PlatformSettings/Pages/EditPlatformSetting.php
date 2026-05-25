<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Pages;

use App\Filament\Platform\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Support\Pages\EditRecordPage;
use App\Services\PlatformMailSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;

class EditPlatformSetting extends EditRecordPage
{
    protected static string $resource = PlatformSettingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function afterSave(): void
    {
        Cache::forget('public_platform_configuration');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_smtp')
                ->label('Tester SMTP')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => $this->record?->key === PlatformMailSettings::SETTING_KEY)
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        app(PlatformMailSettings::class)->sendTestMessage();

                        Notification::make()
                            ->title('E-mail de test envoyé')
                            ->body('Le message a été envoyé vers l’adresse expéditeur configurée.')
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->title('Échec du test SMTP')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}

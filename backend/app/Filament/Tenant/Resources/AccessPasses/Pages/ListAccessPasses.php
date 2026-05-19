<?php

namespace App\Filament\Tenant\Resources\AccessPasses\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\AccessPasses\AccessPassResource;
use Filament\Actions\Action;

class ListAccessPasses extends ListRecordsPage
{
    protected static string $resource = AccessPassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')
                ->label('Scanner / vérifier')
                ->icon('heroicon-o-qr-code')
                ->url(AccessPassResource::getUrl('verify')),
        ];
    }
}

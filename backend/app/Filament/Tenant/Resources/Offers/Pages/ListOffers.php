<?php

namespace App\Filament\Tenant\Resources\Offers\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Offers\OfferResource;
use Filament\Actions\CreateAction;

class ListOffers extends ListRecordsPage
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}

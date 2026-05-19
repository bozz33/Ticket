<?php

namespace App\Filament\Tenant\Resources\Categories\Pages;

use App\Filament\Support\Pages\ManageRecordsPage;
use App\Filament\Tenant\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;

class ManageCategories extends ManageRecordsPage
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

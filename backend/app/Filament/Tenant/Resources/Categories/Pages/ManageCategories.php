<?php

namespace App\Filament\Tenant\Resources\Categories\Pages;

use App\Filament\Tenant\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

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

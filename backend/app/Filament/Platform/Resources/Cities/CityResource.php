<?php

namespace App\Filament\Platform\Resources\Cities;

use App\Filament\Platform\Resources\Cities\Pages\ListCities;
use App\Filament\Platform\Resources\Cities\Schemas\CityForm;
use App\Filament\Platform\Resources\Cities\Tables\CitiesTable;
use App\Models\City;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CityResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = City::class;

    protected static ?string $permissionPrefix = 'platform.references';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListCities::route('/')];
    }
}

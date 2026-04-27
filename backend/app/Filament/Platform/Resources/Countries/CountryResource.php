<?php

namespace App\Filament\Platform\Resources\Countries;

use App\Filament\Platform\Resources\Countries\Pages\ListCountries;
use App\Filament\Platform\Resources\Countries\Schemas\CountryForm;
use App\Filament\Platform\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CountryResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Country::class;

    protected static ?string $permissionPrefix = 'platform.references';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListCountries::route('/')];
    }
}

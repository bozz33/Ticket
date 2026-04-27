<?php

namespace App\Filament\Platform\Resources\Currencies;

use App\Filament\Platform\Resources\Currencies\Pages\ListCurrencies;
use App\Filament\Platform\Resources\Currencies\Schemas\CurrencyForm;
use App\Filament\Platform\Resources\Currencies\Tables\CurrenciesTable;
use App\Models\Currency;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Currency::class;

    protected static ?string $permissionPrefix = 'platform.references';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListCurrencies::route('/')];
    }
}

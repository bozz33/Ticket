<?php

namespace App\Filament\Platform\Resources\PaymentMethodTypes;

use App\Filament\Platform\Resources\PaymentMethodTypes\Pages\ListPaymentMethodTypes;
use App\Filament\Platform\Resources\PaymentMethodTypes\Schemas\PaymentMethodTypeForm;
use App\Filament\Platform\Resources\PaymentMethodTypes\Tables\PaymentMethodTypesTable;
use App\Models\PaymentMethodType;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PaymentMethodTypeResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PaymentMethodType::class;

    protected static ?string $permissionPrefix = 'platform.references';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PaymentMethodTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentMethodTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListPaymentMethodTypes::route('/')];
    }
}

<?php

namespace App\Filament\Platform\Resources\CommercialPolicies;

use App\Filament\Platform\Resources\CommercialPolicies\Pages\CreateCommercialPolicy;
use App\Filament\Platform\Resources\CommercialPolicies\Pages\EditCommercialPolicy;
use App\Filament\Platform\Resources\CommercialPolicies\Pages\ListCommercialPolicies;
use App\Filament\Platform\Resources\CommercialPolicies\Schemas\CommercialPolicyForm;
use App\Filament\Platform\Resources\CommercialPolicies\Tables\CommercialPoliciesTable;
use App\Models\CommercialPolicy;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CommercialPolicyResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = CommercialPolicy::class;

    protected static ?string $permissionPrefix = 'platform.commercial_policies';

    protected static string|UnitEnum|null $navigationGroup = 'Offre & Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Tarification & Commissions';

    protected static ?string $modelLabel = 'Règle tarifaire';

    protected static ?string $pluralModelLabel = 'Tarification & commissions';

    protected static ?string $recordTitleAttribute = 'module';

    public static function form(Schema $schema): Schema
    {
        return CommercialPolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommercialPoliciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommercialPolicies::route('/'),
            'create' => CreateCommercialPolicy::route('/create'),
            'edit' => EditCommercialPolicy::route('/{record}/edit'),
        ];
    }
}

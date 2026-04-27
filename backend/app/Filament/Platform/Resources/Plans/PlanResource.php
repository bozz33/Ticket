<?php

namespace App\Filament\Platform\Resources\Plans;

use App\Filament\Platform\Resources\Plans\Pages\CreatePlan;
use App\Filament\Platform\Resources\Plans\Pages\EditPlan;
use App\Filament\Platform\Resources\Plans\Pages\ListPlans;
use App\Filament\Platform\Resources\Plans\Schemas\PlanForm;
use App\Filament\Platform\Resources\Plans\Tables\PlansTable;
use App\Models\Plan;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlanResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Plan::class;

    protected static ?string $permissionPrefix = 'platform.plans';

    protected static string|UnitEnum|null $navigationGroup = 'Offre & Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Plans SaaS';

    protected static ?string $modelLabel = 'Plan SaaS';

    protected static ?string $pluralModelLabel = 'Plans SaaS';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }
}

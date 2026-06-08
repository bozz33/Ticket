<?php

namespace App\Filament\Tenant\Resources\Stands;

use App\Filament\Tenant\Resources\Stands\Pages\CreateStand;
use App\Filament\Tenant\Resources\Stands\Pages\EditStand;
use App\Filament\Tenant\Resources\Stands\Pages\ListStands;
use App\Filament\Tenant\Resources\Stands\RelationManagers\StandOffersRelationManager;
use App\Filament\Tenant\Resources\Stands\Schemas\StandForm;
use App\Filament\Tenant\Resources\Stands\Tables\StandsTable;
use App\Models\Stand;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StandResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Stand::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Création & modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Stands';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'Stand';

    protected static ?string $pluralModelLabel = 'Stands';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return StandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStands::route('/'),
            'create' => CreateStand::route('/create'),
            'edit' => EditStand::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            StandOffersRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return static::allows('update');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDeleteAny(): bool
    {
        return static::allows('update');
    }
}

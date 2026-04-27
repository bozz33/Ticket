<?php

namespace App\Filament\Platform\Resources\PublicStatuses;

use App\Filament\Platform\Resources\PublicStatuses\Pages\ListPublicStatuses;
use App\Filament\Platform\Resources\PublicStatuses\Schemas\PublicStatusForm;
use App\Filament\Platform\Resources\PublicStatuses\Tables\PublicStatusesTable;
use App\Models\PublicStatus;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PublicStatusResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PublicStatus::class;

    protected static ?string $permissionPrefix = 'platform.references';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-eye';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PublicStatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PublicStatusesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListPublicStatuses::route('/')];
    }
}

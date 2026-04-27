<?php

namespace App\Filament\Platform\Resources\ResourceTypes;

use App\Filament\Platform\Resources\ResourceTypes\Pages\ListResourceTypes;
use App\Filament\Platform\Resources\ResourceTypes\Schemas\ResourceTypeForm;
use App\Filament\Platform\Resources\ResourceTypes\Tables\ResourceTypesTable;
use App\Models\ResourceType;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ResourceTypeResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = ResourceType::class;

    protected static ?string $permissionPrefix = 'platform.references';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ResourceTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResourceTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListResourceTypes::route('/')];
    }
}

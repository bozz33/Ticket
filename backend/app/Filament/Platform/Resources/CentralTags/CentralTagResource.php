<?php

namespace App\Filament\Platform\Resources\CentralTags;

use App\Filament\Platform\Resources\CentralTags\Pages\ListCentralTags;
use App\Filament\Platform\Resources\CentralTags\Schemas\CentralTagForm;
use App\Filament\Platform\Resources\CentralTags\Tables\CentralTagsTable;
use App\Models\CentralTag;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CentralTagResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = CentralTag::class;

    protected static ?string $permissionPrefix = 'platform.tags';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static ?string $modelLabel = 'Tag central';
    protected static ?string $pluralModelLabel = 'Tags centraux';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CentralTagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CentralTagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListCentralTags::route('/')];
    }
}

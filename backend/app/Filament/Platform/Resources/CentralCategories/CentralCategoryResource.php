<?php

namespace App\Filament\Platform\Resources\CentralCategories;

use App\Filament\Platform\Resources\CentralCategories\Pages\ListCentralCategories;
use App\Filament\Platform\Resources\CentralCategories\Schemas\CentralCategoryForm;
use App\Filament\Platform\Resources\CentralCategories\Tables\CentralCategoriesTable;
use App\Models\CentralCategory;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CentralCategoryResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = CentralCategory::class;

    protected static ?string $permissionPrefix = 'platform.categories';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Catégories';
    protected static ?string $modelLabel = 'Catégorie';
    protected static ?string $pluralModelLabel = 'Catégories';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CentralCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CentralCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListCentralCategories::route('/')];
    }
}

<?php

namespace App\Filament\Platform\Resources\FrontMenus;

use App\Filament\Platform\Resources\FrontMenus\Pages\CreateFrontMenu;
use App\Filament\Platform\Resources\FrontMenus\Pages\EditFrontMenu;
use App\Filament\Platform\Resources\FrontMenus\Pages\ListFrontMenus;
use App\Filament\Platform\Resources\FrontMenus\Schemas\FrontMenuForm;
use App\Filament\Platform\Resources\FrontMenus\Tables\FrontMenusTable;
use App\Models\FrontMenu;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FrontMenuResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = FrontMenu::class;

    protected static ?string $permissionPrefix = 'platform.front_menus';

    protected static string|UnitEnum|null $navigationGroup = 'CMS front public';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static ?string $navigationLabel = 'Menus front';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Menu front';

    protected static ?string $pluralModelLabel = 'Menus front';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return FrontMenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FrontMenusTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFrontMenus::route('/'),
            'create' => CreateFrontMenu::route('/create'),
            'edit' => EditFrontMenu::route('/{record}/edit'),
        ];
    }
}

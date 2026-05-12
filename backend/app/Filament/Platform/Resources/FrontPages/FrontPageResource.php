<?php

namespace App\Filament\Platform\Resources\FrontPages;

use App\Filament\Platform\Resources\FrontPages\Pages\CreateFrontPage;
use App\Filament\Platform\Resources\FrontPages\Pages\EditFrontPage;
use App\Filament\Platform\Resources\FrontPages\Pages\ListFrontPages;
use App\Filament\Platform\Resources\FrontPages\Schemas\FrontPageForm;
use App\Filament\Platform\Resources\FrontPages\Tables\FrontPagesTable;
use App\Models\FrontPage;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FrontPageResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = FrontPage::class;

    protected static ?string $permissionPrefix = 'platform.front_pages';

    protected static string|UnitEnum|null $navigationGroup = 'Front public';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Pages front';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Page front';

    protected static ?string $pluralModelLabel = 'Pages front';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return FrontPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FrontPagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFrontPages::route('/'),
            'create' => CreateFrontPage::route('/create'),
            'edit' => EditFrontPage::route('/{record}/edit'),
        ];
    }
}

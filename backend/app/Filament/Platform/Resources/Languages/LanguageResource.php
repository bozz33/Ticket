<?php

namespace App\Filament\Platform\Resources\Languages;

use App\Filament\Platform\Resources\Languages\Pages\ListLanguages;
use App\Filament\Platform\Resources\Languages\Schemas\LanguageForm;
use App\Filament\Platform\Resources\Languages\Tables\LanguagesTable;
use App\Models\Language;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class LanguageResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Language::class;

    protected static ?string $permissionPrefix = 'platform.references';
    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LanguageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListLanguages::route('/')];
    }
}

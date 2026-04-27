<?php

namespace App\Filament\Platform\Resources\PlatformSettings;

use App\Filament\Platform\Resources\PlatformSettings\Pages\CreatePlatformSetting;
use App\Filament\Platform\Resources\PlatformSettings\Pages\EditPlatformSetting;
use App\Filament\Platform\Resources\PlatformSettings\Pages\ListPlatformSettings;
use App\Filament\Platform\Resources\PlatformSettings\Schemas\PlatformSettingForm;
use App\Filament\Platform\Resources\PlatformSettings\Tables\PlatformSettingsTable;
use App\Models\PlatformSetting;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlatformSettingResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformSetting::class;

    protected static ?string $permissionPrefix = 'platform.platform_settings';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuration globale';

    protected static ?string $modelLabel = 'Paramètre global';

    protected static ?string $pluralModelLabel = 'Configuration globale';

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return PlatformSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformSettings::route('/'),
            'create' => CreatePlatformSetting::route('/create'),
            'edit' => EditPlatformSetting::route('/{record}/edit'),
        ];
    }
}

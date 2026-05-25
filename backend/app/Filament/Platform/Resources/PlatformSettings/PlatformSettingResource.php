<?php

namespace App\Filament\Platform\Resources\PlatformSettings;

use App\Filament\Platform\Resources\PlatformSettings\Pages\CreatePlatformSetting;
use App\Filament\Platform\Resources\PlatformSettings\Pages\EditPlatformSetting;
use App\Filament\Platform\Resources\PlatformSettings\Pages\ListPlatformSettings;
use App\Filament\Platform\Resources\PlatformSettings\Schemas\PlatformSettingForm;
use App\Filament\Platform\Resources\PlatformSettings\Tables\PlatformSettingsTable;
use App\Models\PlatformSetting;
use App\Services\FinancePolicyService;
use App\Services\PlatformMailSettings;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PlatformSettingResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformSetting::class;

    protected static ?string $permissionPrefix = 'platform.platform_settings';

    protected static string|UnitEnum|null $navigationGroup = 'CMS front public';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Identité & front public';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Paramètre front';

    protected static ?string $pluralModelLabel = 'Identité & front public';

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_public', true)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('group')
                ->orWhere('group', '!=', 'seo'))
            ->whereNotIn('key', [
                FinancePolicyService::SETTING_KEY,
                PlatformMailSettings::SETTING_KEY,
                'mobile_money_providers',
                'default_currency',
            ]);
    }
}

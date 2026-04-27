<?php

namespace App\Filament\Platform\Resources\PlatformUsers;

use App\Filament\Platform\Resources\PlatformUsers\Pages\CreatePlatformUser;
use App\Filament\Platform\Resources\PlatformUsers\Pages\EditPlatformUser;
use App\Filament\Platform\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Filament\Platform\Resources\PlatformUsers\Schemas\PlatformUserForm;
use App\Filament\Platform\Resources\PlatformUsers\Tables\PlatformUsersTable;
use App\Models\PlatformUser;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlatformUserResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformUser::class;

    protected static ?string $permissionPrefix = 'platform.platform_users';

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $modelLabel = 'Utilisateur plateforme';

    protected static ?string $pluralModelLabel = 'Utilisateurs plateforme';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PlatformUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformUsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformUsers::route('/'),
            'create' => CreatePlatformUser::route('/create'),
            'edit' => EditPlatformUser::route('/{record}/edit'),
        ];
    }
}

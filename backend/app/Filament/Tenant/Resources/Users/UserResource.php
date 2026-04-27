<?php

namespace App\Filament\Tenant\Resources\Users;

use App\Filament\Tenant\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UserResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = User::class;

    protected static ?string $permissionPrefix = 'tenant.users';

    protected static ?string $requiredTenantFeature = 'tenant.users';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Utilisateur')->schema([
                    TextInput::make('name')->label('Nom')->required()->maxLength(255),
                    TextInput::make('username')->label('Username')->required()->maxLength(120)->unique(ignoreRecord: true),
                    TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('password')->label('Mot de passe')->password()->revealable()->required(fn (?string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state))->minLength(8)->maxLength(255),
                    TextInput::make('phone')->label('Téléphone')->maxLength(50),
                    TextInput::make('locale')->label('Locale')->default('fr')->maxLength(10),
                    TextInput::make('timezone')->label('Timezone')->default('UTC')->maxLength(100),
                    Toggle::make('is_active')->label('Actif')->default(true),
                    Select::make('roles')
                        ->label('Rôles')
                        ->relationship('roles', 'name', fn (Builder $query) => $query->where('guard_name', 'tenant'))
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                    Select::make('permissions')
                        ->label('Permissions directes')
                        ->relationship('permissions', 'name', fn (Builder $query) => $query->where('guard_name', 'tenant'))
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('username')->label('Username')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('roles.name')->label('Rôles')->badge()->separator(','),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

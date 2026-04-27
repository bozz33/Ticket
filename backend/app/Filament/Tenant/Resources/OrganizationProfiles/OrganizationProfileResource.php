<?php

namespace App\Filament\Tenant\Resources\OrganizationProfiles;

use App\Filament\Tenant\Resources\OrganizationProfiles\Pages\ManageOrganizationProfiles;
use App\Models\OrganizationProfile;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrganizationProfileResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = OrganizationProfile::class;

    protected static ?string $permissionPrefix = 'tenant.settings';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Organisation';

    protected static ?string $modelLabel = 'Organisation';

    protected static ?string $pluralModelLabel = 'Organisation';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil organisation')->schema([
                    TextInput::make('legal_name')->label('Raison sociale')->maxLength(255),
                    TextInput::make('display_name')->label('Nom affiché')->maxLength(255),
                    TextInput::make('email')->label('Email')->email()->maxLength(255),
                    TextInput::make('phone')->label('Téléphone')->maxLength(50),
                    TextInput::make('website_url')->label('Site web')->url()->maxLength(255),
                    TextInput::make('logo_url')->label('Logo URL')->url()->maxLength(255),
                    TextInput::make('banner_url')->label('Bannière URL')->url()->maxLength(255),
                    TextInput::make('primary_color')->label('Couleur primaire')->maxLength(20),
                    TextInput::make('secondary_color')->label('Couleur secondaire')->maxLength(20),
                    TextInput::make('address_line_1')->label('Adresse ligne 1')->maxLength(255),
                    TextInput::make('address_line_2')->label('Adresse ligne 2')->maxLength(255),
                    TextInput::make('city')->label('Ville')->maxLength(120),
                    TextInput::make('state')->label('Région / État')->maxLength(120),
                    TextInput::make('country_code')->label('Pays')->maxLength(2),
                    Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
                    KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('display_name')->label('Nom affiché')->searchable(),
                TextColumn::make('legal_name')->label('Raison sociale')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('Téléphone'),
                TextColumn::make('website_url')->label('Site web')->limit(30),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrganizationProfiles::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return static::allows('update') && ! static::getEloquentQuery()->exists();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

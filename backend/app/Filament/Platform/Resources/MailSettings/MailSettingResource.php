<?php

namespace App\Filament\Platform\Resources\MailSettings;

use App\Filament\Platform\Resources\MailSettings\Pages\EditMailSetting;
use App\Filament\Platform\Resources\MailSettings\Pages\ListMailSettings;
use App\Models\PlatformSetting;
use App\Services\PlatformMailSettings;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MailSettingResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformSetting::class;

    protected static ?string $permissionPrefix = 'platform.platform_settings';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Configuration SMTP';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Configuration SMTP';

    protected static ?string $pluralModelLabel = 'SMTP';

    protected static ?string $recordTitleAttribute = 'key';

    protected static ?string $slug = 'mail-settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Serveur SMTP')->schema([
                    Hidden::make('group')->default('mail')->dehydrated(),
                    Hidden::make('key')->default(PlatformMailSettings::SETTING_KEY)->dehydrated(),
                    Hidden::make('type')->default('json')->dehydrated(),
                    Hidden::make('is_public')->default(false)->dehydrated(),
                    TextInput::make('value.host')
                        ->label('Serveur SMTP')
                        ->placeholder('smtp.example.com')
                        ->maxLength(255)
                        ->columnSpan(3),
                    TextInput::make('value.port')
                        ->label('Port')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(65535)
                        ->default(587)
                        ->columnSpan(1),
                    Select::make('value.encryption')
                        ->label('Chiffrement')
                        ->options([
                            '' => 'Aucun',
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                        ])
                        ->default('tls')
                        ->native(false)
                        ->columnSpan(2),
                    TextInput::make('value.username')
                        ->label('Utilisateur')
                        ->maxLength(255)
                        ->columnSpan(3),
                    TextInput::make('value.password')
                        ->label('Mot de passe')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->columnSpan(3),
                    TextInput::make('value.from_address')
                        ->label('Adresse expéditeur')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),
                    TextInput::make('value.from_name')
                        ->label('Nom expéditeur')
                        ->maxLength(255)
                        ->columnSpan(3),
                ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value.host')
                    ->label('Serveur')
                    ->placeholder('Non configuré'),
                TextColumn::make('value.port')
                    ->label('Port')
                    ->placeholder('587'),
                TextColumn::make('value.from_address')
                    ->label('Expéditeur')
                    ->placeholder('Non configuré'),
                IconColumn::make('is_configured')
                    ->label('Actif')
                    ->getStateUsing(fn (PlatformSetting $record): bool => filled(data_get($record->value, 'host')) && filled(data_get($record->value, 'from_address')))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailSettings::route('/'),
            'edit' => EditMailSetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('key', PlatformMailSettings::SETTING_KEY);
    }

    public static function canCreate(): bool
    {
        return false;
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

<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Schemas;

use App\Services\PlatformMailSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;

class PlatformSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Politique financière')->schema([
                TextInput::make('value.commission_rate')
                    ->label('Taux de commission (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->placeholder('Laisser vide = 0 %')
                    ->helperText('Commission retenue sur le reversement organisateur. Les frais gateway restent absorbés par la plateforme.')
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isFinancePolicy($get('key'), $record?->key ?? null)),
                TextInput::make('value.card_fee_per_ticket')
                    ->label('Frais carte par ticket (FCFA)')
                    ->numeric()
                    ->minValue(0)
                    ->placeholder('Laisser vide = 0 FCFA')
                    ->helperText('Appliqué uniquement aux paiements par carte, par ticket. Non remboursable sauf erreur technique confirmée ou débit en doublon.')
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isFinancePolicy($get('key'), $record?->key ?? null)),
            ])->columns(6),
            Section::make('Configuration globale')->schema([
                TextInput::make('group')
                    ->label('Groupe')
                    ->maxLength(100)
                    ->columnSpan(2)
                    ->hidden(fn ($get, $record): bool => static::isFinancePolicy($get('key'), $record?->key ?? null)),
                TextInput::make('key')
                    ->label('Clé')
                    ->required()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true)
                    ->columnSpan(3),
                TextInput::make('type')
                    ->label('Type')
                    ->default('json')
                    ->required()
                    ->maxLength(50)
                    ->columnSpan(1)
                    ->hidden(fn ($get, $record): bool => static::isFinancePolicy($get('key'), $record?->key ?? null)),
                Toggle::make('is_public')
                    ->label('Public')
                    ->default(false)
                    ->inline(false)
                    ->columnSpan(2)
                    ->hidden(fn ($get, $record): bool => static::isFinancePolicy($get('key'), $record?->key ?? null)),
                KeyValue::make('value')
                    ->label('Valeur')
                    ->columnSpanFull()
                    ->hidden(fn ($get, $record): bool => static::isSpecializedSetting($get('key'), $record?->key ?? null)),
            ])->columns(6),
            Section::make('Identité visuelle plateforme')->schema([
                TextInput::make('value.platform_name')
                    ->label('Nom affiché')
                    ->helperText('Nom utilisé dans le header public, les métadonnées et les documents plateforme.')
                    ->maxLength(80)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isBrandingSetting($get('key'), $record?->key ?? null)),
                TextInput::make('value.tagline')
                    ->label('Signature')
                    ->helperText('Texte court affichable sous le nom de marque.')
                    ->maxLength(120)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isBrandingSetting($get('key'), $record?->key ?? null)),
                FileUpload::make('value.logo_url')
                    ->label('Logo header')
                    ->helperText('Format recommandé : PNG/SVG carré 256 x 256 px minimum. Le header l’affiche en 40 x 40 px sans déformation.')
                    ->image()
                    ->disk('public')
                    ->directory('platform/branding')
                    ->visibility('public')
                    ->maxSize(1024)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isBrandingSetting($get('key'), $record?->key ?? null)),
                FileUpload::make('value.favicon_url')
                    ->label('Favicon')
                    ->helperText('Format recommandé : PNG carré 512 x 512 px, lisible en 32 x 32 px.')
                    ->image()
                    ->disk('public')
                    ->directory('platform/branding/favicons')
                    ->visibility('public')
                    ->maxSize(512)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isBrandingSetting($get('key'), $record?->key ?? null)),
                FileUpload::make('value.apple_touch_icon_url')
                    ->label('Icône Apple / mobile')
                    ->helperText('Format recommandé : PNG carré 180 x 180 px ou supérieur.')
                    ->image()
                    ->disk('public')
                    ->directory('platform/branding/favicons')
                    ->visibility('public')
                    ->maxSize(512)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isBrandingSetting($get('key'), $record?->key ?? null)),
            ])->columns(6),
            Section::make('Configuration SMTP')->schema([
                TextInput::make('value.host')
                    ->label('Serveur SMTP')
                    ->maxLength(255)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null)),
                TextInput::make('value.port')
                    ->label('Port')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(65535)
                    ->default(587)
                    ->columnSpan(1)
                    ->visible(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null)),
                Select::make('value.encryption')
                    ->label('Chiffrement')
                    ->options([
                        '' => 'Aucun',
                        'tls' => 'TLS',
                        'ssl' => 'SSL',
                    ])
                    ->native(false)
                    ->columnSpan(2)
                    ->visible(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null)),
                TextInput::make('value.username')
                    ->label('Utilisateur')
                    ->maxLength(255)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null)),
                TextInput::make('value.password')
                    ->label('Mot de passe')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null)),
                TextInput::make('value.from_address')
                    ->label('Adresse expéditeur')
                    ->email()
                    ->required(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null))
                    ->maxLength(255)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null)),
                TextInput::make('value.from_name')
                    ->label('Nom expéditeur')
                    ->maxLength(255)
                    ->columnSpan(3)
                    ->visible(fn ($get, $record): bool => static::isMailSetting($get('key'), $record?->key ?? null)),
            ])->columns(6),
        ]);
    }

    private static function isFinancePolicy(?string $formKey, ?string $recordKey): bool
    {
        return ($formKey ?? $recordKey) === FinancePolicyCatalog::SETTING_KEY;
    }

    private static function isMailSetting(?string $formKey, ?string $recordKey): bool
    {
        return ($formKey ?? $recordKey) === PlatformMailSettings::SETTING_KEY;
    }

    private static function isBrandingSetting(?string $formKey, ?string $recordKey): bool
    {
        return ($formKey ?? $recordKey) === 'public_branding';
    }

    private static function isSpecializedSetting(?string $formKey, ?string $recordKey): bool
    {
        return static::isFinancePolicy($formKey, $recordKey)
            || static::isMailSetting($formKey, $recordKey)
            || static::isBrandingSetting($formKey, $recordKey);
    }
}

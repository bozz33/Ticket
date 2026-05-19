<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Schemas;

use App\Services\FinancePolicyService;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                    ->hidden(fn ($get, $record): bool => static::isFinancePolicy($get('key'), $record?->key ?? null)),
            ])->columns(6),
        ]);
    }

    private static function isFinancePolicy(?string $formKey, ?string $recordKey): bool
    {
        return ($formKey ?? $recordKey) === FinancePolicyService::SETTING_KEY;
    }
}

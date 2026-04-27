<?php

namespace App\Filament\Platform\Resources\CommercialPolicies\Schemas;

use App\Enums\CommercialModule;
use App\Enums\MonetizationMode;
use App\Models\Plan;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommercialPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tarification')->schema([
                Select::make('module')->label('Module métier')->options(CommercialModule::options())->required()->unique(ignoreRecord: true)->columnSpan(2),
                Select::make('monetization_mode')->label('Modèle économique')->options(MonetizationMode::options())->required()->default(MonetizationMode::Free->value)->columnSpan(2),
                Select::make('plan_id')->label('Plan de référence')->options(fn (): array => Plan::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->columnSpan(2),
                TextInput::make('commission_rate')->label('Commission %')->numeric()->minValue(0)->maxValue(100)->columnSpan(2),
                TextInput::make('flat_fee_amount')->label('Frais fixes')->numeric()->minValue(0)->columnSpan(2),
                TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3)->required()->columnSpan(1),
                Toggle::make('is_active')->label('Actif')->default(true)->inline(false)->columnSpan(1),
                KeyValue::make('meta')->label('Règles avancées')->columnSpanFull(),
            ])->columns(6),
        ]);
    }
}

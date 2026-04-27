<?php

namespace App\Filament\Platform\Resources\Plans\Schemas;

use App\Enums\BillingInterval;
use App\Models\FeatureFlag;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plan SaaS')->schema([
                TextInput::make('code')->label('Code')->required()->maxLength(255)->columnSpan(2),
                TextInput::make('name')->label('Nom')->required()->maxLength(255)->columnSpan(4),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                TextInput::make('price_amount')->label('Prix')->numeric()->default(0)->required()->columnSpan(2),
                TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3)->required()->columnSpan(1),
                Select::make('billing_interval')->label('Intervalle')->options(BillingInterval::options())->required()->default(BillingInterval::Monthly->value)->columnSpan(2),
                TextInput::make('trial_days')->label('Jours d\'essai')->numeric()->default(0)->required()->columnSpan(1),
                Toggle::make('is_active')->label('Actif')->default(true)->inline(false)->columnSpan(2),
                Select::make('meta.features')
                    ->label('Modules inclus')
                    ->options(fn (): array => FeatureFlag::query()->where('is_active', true)->orderBy('module')->orderBy('name')->pluck('name', 'code')->all())
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
            ])->columns(6),
        ]);
    }
}

<?php

namespace App\Filament\Platform\Resources\Currencies\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Devise')->schema([
                TextInput::make('code')->label('Code')->required()->maxLength(3)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('symbol')->label('Symbole')->maxLength(20),
                TextInput::make('decimal_places')->label('Décimales')->numeric()->default(0),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                Toggle::make('is_active')->label('Actif')->default(true),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

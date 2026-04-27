<?php

namespace App\Filament\Platform\Resources\Countries\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pays')->schema([
                TextInput::make('iso2')->label('ISO2')->required()->maxLength(2)->unique(ignoreRecord: true),
                TextInput::make('iso3')->label('ISO3')->maxLength(3)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('phone_code')->label('Code téléphonique')->maxLength(20),
                TextInput::make('currency_code')->label('Devise')->maxLength(3),
                TextInput::make('language_code')->label('Langue')->maxLength(10),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                Toggle::make('is_active')->label('Actif')->default(true),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

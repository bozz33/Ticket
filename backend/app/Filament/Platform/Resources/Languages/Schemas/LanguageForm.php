<?php

namespace App\Filament\Platform\Resources\Languages\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Langue')->schema([
                TextInput::make('code')->label('Code')->required()->maxLength(10)->unique(ignoreRecord: true),
                TextInput::make('locale')->label('Locale')->required()->maxLength(10)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('native_name')->label('Nom natif')->maxLength(255),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                Toggle::make('is_active')->label('Actif')->default(true),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

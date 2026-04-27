<?php

namespace App\Filament\Platform\Resources\PublicStatuses\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PublicStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Statut public')->schema([
                TextInput::make('code')->label('Code')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                TextInput::make('color')->label('Couleur')->maxLength(50),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                Toggle::make('is_active')->label('Actif')->default(true),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

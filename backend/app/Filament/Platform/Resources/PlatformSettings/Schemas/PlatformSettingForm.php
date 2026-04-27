<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Schemas;

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
            Section::make('Configuration globale')->schema([
                TextInput::make('group')->label('Groupe')->maxLength(100)->columnSpan(2),
                TextInput::make('key')->label('Clé')->required()->maxLength(150)->unique(ignoreRecord: true)->columnSpan(3),
                TextInput::make('type')->label('Type')->default('json')->required()->maxLength(50)->columnSpan(1),
                Toggle::make('is_public')->label('Public')->default(false)->inline(false)->columnSpan(2),
                KeyValue::make('value')->label('Valeur')->columnSpanFull(),
            ])->columns(6),
        ]);
    }
}

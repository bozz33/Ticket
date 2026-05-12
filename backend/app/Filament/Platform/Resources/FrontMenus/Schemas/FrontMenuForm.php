<?php

namespace App\Filament\Platform\Resources\FrontMenus\Schemas;

use App\Enums\FrontMenuLocation;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FrontMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu')->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->columnSpan(2),
                TextInput::make('key')->label('Clé')->required()->maxLength(120)->unique(ignoreRecord: true)->columnSpan(2),
                Select::make('location')->label('Emplacement')->options(FrontMenuLocation::options())->required()->columnSpan(1),
                Toggle::make('is_active')->label('Actif')->default(true)->inline(false)->columnSpan(1),
                KeyValue::make('settings')->label('Settings')->columnSpanFull(),
            ])->columns(6),
            Section::make('Liens')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                    ->schema([
                        TextInput::make('label')->label('Label')->required()->maxLength(255)->columnSpan(1),
                        TextInput::make('href')->label('URL')->required()->maxLength(255)->columnSpan(1),
                        Select::make('target')
                            ->label('Target')
                            ->options([
                                '_self' => 'Même onglet',
                                '_blank' => 'Nouvel onglet',
                            ])
                            ->default('_self')
                            ->required()
                            ->columnSpan(1),
                        Toggle::make('is_active')->label('Actif')->default(true)->inline(false)->columnSpan(1),
                        KeyValue::make('meta')->label('Meta lien')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }
}

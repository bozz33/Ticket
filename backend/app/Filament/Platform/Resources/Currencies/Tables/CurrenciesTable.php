<?php

namespace App\Filament\Platform\Resources\Currencies\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Code')->searchable(),
            TextColumn::make('name')->label('Nom')->searchable(),
            TextColumn::make('symbol')->label('Symbole'),
            TextColumn::make('decimal_places')->label('Décimales'),
            IconColumn::make('is_active')->label('Actif')->boolean(),
        ])->defaultSort('sort_order')->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            CreateAction::make(),
        ]);
    }
}

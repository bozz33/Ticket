<?php

namespace App\Filament\Platform\Resources\Cities\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nom')->searchable(),
            TextColumn::make('country.name')->label('Pays')->searchable(),
            TextColumn::make('slug')->label('Slug'),
            IconColumn::make('is_active')->label('Actif')->boolean(),
        ])->defaultSort('sort_order')->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            CreateAction::make(),
        ]);
    }
}

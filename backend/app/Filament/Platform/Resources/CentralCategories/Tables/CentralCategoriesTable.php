<?php

namespace App\Filament\Platform\Resources\CentralCategories\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CentralCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nom')->searchable(),
            TextColumn::make('parent.name')->label('Parent'),
            TextColumn::make('module_scope')->label('Scope')->badge(),
            TextColumn::make('sort_order')->label('Ordre')->sortable(),
            IconColumn::make('is_active')->label('Actif')->boolean(),
        ])->defaultSort('sort_order')->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            CreateAction::make(),
        ]);
    }
}

<?php

namespace App\Filament\Platform\Resources\CentralTags\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CentralTagsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nom')->searchable(),
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

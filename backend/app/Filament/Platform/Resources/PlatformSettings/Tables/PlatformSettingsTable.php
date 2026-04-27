<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlatformSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')->label('Groupe')->searchable(),
                TextColumn::make('key')->label('Clé')->searchable(),
                TextColumn::make('type')->label('Type')->badge(),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime()->sortable(),
            ])
            ->defaultSort('group')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

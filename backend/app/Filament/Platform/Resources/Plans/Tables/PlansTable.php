<?php

namespace App\Filament\Platform\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('code')->label('Code')->badge()->searchable(),
                TextColumn::make('price_amount')->label('Prix')->money('XOF', divideBy: 1)->sortable(),
                TextColumn::make('billing_interval')->label('Intervalle')->badge(),
                TextColumn::make('trial_days')->label('Essai'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('price_amount')
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

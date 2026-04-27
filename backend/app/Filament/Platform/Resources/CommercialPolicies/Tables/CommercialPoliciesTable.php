<?php

namespace App\Filament\Platform\Resources\CommercialPolicies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommercialPoliciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module')->label('Module métier')->badge(),
                TextColumn::make('monetization_mode')->label('Modèle économique')->badge(),
                TextColumn::make('plan.name')->label('Plan de référence'),
                TextColumn::make('commission_rate')->label('Commission %'),
                TextColumn::make('flat_fee_amount')->label('Frais fixes')->numeric(),
                TextColumn::make('currency_code')->label('Devise'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('module')
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

<?php

namespace App\Filament\Tenant\Resources\Stands\Tables;

use App\Filament\Tenant\Resources\Stands\StandResource;
use App\Models\Stand;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StandsTable
{
    public static function configure(Table $table): Table
    {
        return $table->recordTitleAttribute('name')->columns([
            TextColumn::make('name')->label('Nom')->searchable(),
            TextColumn::make('public_status_code')->label('Statut public')->badge(),
            TextColumn::make('price_amount')->label('Prix')->numeric(),
            TextColumn::make('quantity_available')->label('Disponibles')->numeric(),
            IconColumn::make('is_active')->label('Actif')->boolean(),
            TextColumn::make('updated_at')->label('Mis à jour')->since(),
        ])->defaultSort('updated_at', 'desc')->recordActions([
            EditAction::make()
                ->url(fn (Stand $record): string => StandResource::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }
}

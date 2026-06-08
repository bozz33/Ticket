<?php

namespace App\Filament\Tenant\Resources\Trainings\Tables;

use App\Filament\Tenant\Resources\Trainings\TrainingResource;
use App\Models\Training;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrainingsTable
{
    public static function configure(Table $table): Table
    {
        return $table->recordTitleAttribute('title')->columns([
            TextColumn::make('title')->label('Titre')->searchable(),
            TextColumn::make('public_status_code')->label('Statut public')->badge(),
            TextColumn::make('starts_at')->label('Début')->dateTime('d/m/Y H:i'),
            IconColumn::make('is_active')->label('Actif')->boolean(),
            TextColumn::make('updated_at')->label('Mis à jour')->since(),
        ])->defaultSort('updated_at', 'desc')->recordActions([
            EditAction::make()
                ->url(fn (Training $record): string => TrainingResource::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }
}

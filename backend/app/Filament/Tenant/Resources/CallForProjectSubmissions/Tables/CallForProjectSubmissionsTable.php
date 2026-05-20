<?php

namespace App\Filament\Tenant\Resources\CallForProjectSubmissions\Tables;

use App\Filament\Tenant\Resources\CallForProjectSubmissions\CallForProjectSubmissionResource;
use App\Models\CallForProjectSubmission;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallForProjectSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('callForProject'))
            ->columns([
                TextColumn::make('callForProject.title')->label('Appel à projets')->searchable(),
                TextColumn::make('applicant_name')->label('Candidat')->searchable(),
                TextColumn::make('applicant_email')->label('E-mail')->searchable(),
                TextColumn::make('city_name')->label('Ville')->toggleable(),
                TextColumn::make('country_code')->label('Pays')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('meta.review_score')->label('Score')->sortable()->toggleable(),
                TextColumn::make('submitted_at')->label('Soumise le')->dateTime(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Voir')
                    ->url(fn (CallForProjectSubmission $record): string => CallForProjectSubmissionResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

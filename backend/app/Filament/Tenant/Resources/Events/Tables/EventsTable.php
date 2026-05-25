<?php

namespace App\Filament\Tenant\Resources\Events\Tables;

use App\Filament\Tenant\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('category.name')->label('Catégorie')->badge(),
                TextColumn::make('public_status_code')
                    ->label('Statut public')
                    ->formatStateUsing(function (?string $state, Event $record): string {
                        $lastScheduledAt = $record->dates
                            ->map(fn ($date) => $date->ends_at ?? $date->starts_at)
                            ->filter()
                            ->max();

                        if ($lastScheduledAt && Carbon::parse($lastScheduledAt)->isPast()) {
                            return 'Terminé';
                        }

                        return (string) ($state ?: '—');
                    })
                    ->color(function (?string $state, Event $record): string {
                        $lastScheduledAt = $record->dates
                            ->map(fn ($date) => $date->ends_at ?? $date->starts_at)
                            ->filter()
                            ->max();

                        if ($lastScheduledAt && Carbon::parse($lastScheduledAt)->isPast()) {
                            return 'gray';
                        }

                        return $state === 'published' ? 'success' : 'warning';
                    })
                    ->badge(),
                TextColumn::make('venue_name')->label('Lieu'),
                TextColumn::make('currency_code')->label('Devise'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('published_at')->label('Publié le')->dateTime(),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('dates'))
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->url(fn (Event $record): string => EventResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

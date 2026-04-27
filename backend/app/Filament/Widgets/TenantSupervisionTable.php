<?php

namespace App\Filament\Widgets;

use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TenantSupervisionTable extends TableWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()
                    ->withCount([
                        'incidentLogs as open_incidents_count' => fn (Builder $query) => $query->where('status', '!=', 'resolved'),
                        'settlements as pending_settlements_count' => fn (Builder $query) => $query->whereIn('status', ['draft', 'scheduled']),
                    ])
                    ->orderBy('name')
            )
            ->recordUrl(fn (Tenant $record): string => TenantResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label('Tenant')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('active_subscription')
                    ->label('Souscription')
                    ->state(fn (Tenant $record): string => $record->activeSubscription()?->status?->value ?? 'inactive')
                    ->badge(),
                TextColumn::make('open_incidents_count')->label('Incidents ouverts')->numeric(),
                TextColumn::make('pending_settlements_count')->label('Reversements attente')->numeric(),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ]);
    }
}

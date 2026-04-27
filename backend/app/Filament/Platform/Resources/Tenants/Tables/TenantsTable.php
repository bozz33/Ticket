<?php

namespace App\Filament\Platform\Resources\Tenants\Tables;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Tenancy\DeleteTenant;
use App\Services\Tenancy\ManageTenantLifecycle;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('domains.domain')->label('Sous-domaine')->badge()->separator(',')->toggleable(),
                TextColumn::make('created_at')->label('Créé le')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('activate')
                    ->label('Activer')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->status !== TenantStatus::Active)
                    ->action(fn (Tenant $record) => app(ManageTenantLifecycle::class)->activate($record)),
                Action::make('suspend')
                    ->label('Suspendre')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::Active)
                    ->action(fn (Tenant $record) => app(ManageTenantLifecycle::class)->suspend($record)),
                Action::make('archive')
                    ->label('Archiver')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->status !== TenantStatus::Archived)
                    ->action(fn (Tenant $record) => app(ManageTenantLifecycle::class)->archive($record)),
                EditAction::make(),
                DeleteAction::make()
                    ->label('Supprimer')
                    ->requiresConfirmation()
                    ->modalDescription('Cette action supprime définitivement le tenant, son sous-domaine, sa base de données et son dossier de stockage.')
                    ->action(fn (Tenant $record): mixed => app(DeleteTenant::class)->handle($record)),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

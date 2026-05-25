<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContributionsRelationManager extends RelationManager
{
    protected static string $relationship = 'contributions';

    protected static ?string $title = 'Contributions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contributor_name')
            ->columns([
                TextColumn::make('contributor_name')->label('Contributeur')->searchable(),
                TextColumn::make('contributor_email')->label('E-mail')->searchable(),
                TextColumn::make('amount')->label('Montant')->money(fn ($record): string => $record->currency_code ?? 'XOF')->sortable(),
                TextColumn::make('refunded_amount')->label('Remboursé')->money(fn ($record): string => $record->currency_code ?? 'XOF')->sortable(),
                TextColumn::make('status')->label('Statut')->badge(),
                IconColumn::make('is_anonymous')->label('Anonyme')->boolean(),
                TextColumn::make('paid_at')->label('Payé le')->dateTime()->sortable(),
                TextColumn::make('refunded_at')->label('Remboursé le')->dateTime()->sortable(),
            ])
            ->defaultSort('paid_at', 'desc');
    }
}

<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\Tables;

use App\Filament\Tenant\Resources\CrowdfundingCampaigns\CrowdfundingCampaignResource;
use App\Models\CrowdfundingCampaign;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CrowdfundingCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table->recordTitleAttribute('title')->columns([
            TextColumn::make('title')->label('Titre')->searchable(),
            TextColumn::make('public_status_code')->label('Statut public')->badge(),
            TextColumn::make('target_amount')->label('Objectif')->numeric(),
            TextColumn::make('raised_amount')->label('Collecté')->numeric(),
            IconColumn::make('is_active')->label('Actif')->boolean(),
            TextColumn::make('updated_at')->label('Mis à jour')->since(),
        ])->defaultSort('updated_at', 'desc')->recordActions([
            EditAction::make()
                ->url(fn (CrowdfundingCampaign $record): string => CrowdfundingCampaignResource::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }
}

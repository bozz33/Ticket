<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContributionOffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'Paliers de contribution';

    protected static ?string $modelLabel = 'palier';

    protected static ?string $pluralModelLabel = 'paliers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Palier de contribution')->schema([
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('code')->label('Code')->maxLength(100)->unique(ignoreRecord: true),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                TextInput::make('offer_type')->label('Type')->default('contribution')->required()->maxLength(80),
                TextInput::make('price_amount')->label('Montant')->numeric()->required()->default(0),
                TextInput::make('currency_code')->label('Devise')->default(fn (): ?string => $this->getOwnerRecord()->currency_code)->maxLength(3),
                TextInput::make('quantity_total')->label('Nombre maximum')->numeric(),
                TextInput::make('quantity_sold')->label('Contributions validées')->numeric()->default(0)->disabled()->dehydrated(),
                TextInput::make('min_per_order')->label('Minimum par contribution')->numeric()->default(1),
                TextInput::make('max_per_order')->label('Maximum par contribution')->numeric()->default(1),
                DateTimePicker::make('sales_start_at')->label('Début disponibilité'),
                DateTimePicker::make('sales_end_at')->label('Fin disponibilité'),
                Toggle::make('is_active')->label('Actif')->default(true),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                KeyValue::make('meta')->label('Avantages / métadonnées')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Palier')->searchable(),
                TextColumn::make('price_amount')->label('Montant')->numeric(),
                TextColumn::make('currency_code')->label('Devise')->badge(),
                TextColumn::make('quantity_sold')->label('Validées')->numeric(),
                TextColumn::make('quantity_total')->label('Maximum')->numeric(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

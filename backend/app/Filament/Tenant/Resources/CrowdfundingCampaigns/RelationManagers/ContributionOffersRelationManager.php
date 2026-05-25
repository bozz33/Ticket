<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
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
                Hidden::make('offer_type')->default('contribution')->dehydrated(),
                Hidden::make('sort_order')->default(0)->dehydrated(),
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('price_amount')->label('Montant')->numeric()->required()->minValue(1),
                TextInput::make('currency_code')->label('Devise')->default(fn (): ?string => $this->getOwnerRecord()->currency_code ?: 'XOF')->maxLength(3),
                TextInput::make('quantity_total')->label('Nombre maximum')->numeric()->minValue(1),
                TextInput::make('min_per_order')->label('Minimum par contribution')->numeric()->default(1)->minValue(1),
                TextInput::make('max_per_order')->label('Maximum par contribution')->numeric()->default(1)->minValue(1),
                DateTimePicker::make('sales_start_at')
                    ->label('Début disponibilité')
                    ->maxDate(fn (): mixed => $this->getOwnerRecord()->ends_at ?? data_get($this->getOwnerRecord()->meta, 'event_at')),
                DateTimePicker::make('sales_end_at')
                    ->label('Fin disponibilité')
                    ->helperText('La fin de disponibilité doit rester avant la fin de collecte ou la date de l’activité.')
                    ->maxDate(fn (): mixed => $this->getOwnerRecord()->ends_at ?? data_get($this->getOwnerRecord()->meta, 'event_at')),
                Toggle::make('is_active')->label('Actif')->default(true),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
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

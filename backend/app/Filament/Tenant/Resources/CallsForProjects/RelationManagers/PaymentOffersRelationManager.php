<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\RelationManagers;

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

class PaymentOffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'Tarifs de candidature';

    protected static ?string $modelLabel = 'tarif';

    protected static ?string $pluralModelLabel = 'tarifs';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tarif / ticket de candidature')->schema([
                Hidden::make('offer_type')->default('application_fee')->dehydrated(),
                TextInput::make('name')->label('Nom')->placeholder('Pass candidature')->required()->maxLength(255),
                TextInput::make('price_amount')->label('Montant')->numeric()->required()->minValue(1),
                TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3),
                TextInput::make('quantity_total')->label('Nombre maximum')->numeric()->minValue(1),
                TextInput::make('min_per_order')->label('Minimum par commande')->numeric()->default(1)->minValue(1),
                TextInput::make('max_per_order')->label('Maximum par commande')->numeric()->default(1)->minValue(1),
                DateTimePicker::make('sales_start_at')
                    ->label('Début de vente')
                    ->maxDate(fn (): mixed => $this->getOwnerRecord()->application_closes_at ?? data_get($this->getOwnerRecord()->meta, 'event_at')),
                DateTimePicker::make('sales_end_at')
                    ->label('Fin de vente')
                    ->helperText('La fin de vente doit rester avant la clôture des candidatures ou la date de l’activité.')
                    ->maxDate(fn (): mixed => $this->getOwnerRecord()->application_closes_at ?? data_get($this->getOwnerRecord()->meta, 'event_at')),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Actif')->default(true),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Tarif')->searchable(),
                TextColumn::make('price_amount')->label('Montant')->numeric(),
                TextColumn::make('currency_code')->label('Devise')->badge(),
                TextColumn::make('quantity_total')->label('Limite')->numeric()->placeholder('Illimité'),
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

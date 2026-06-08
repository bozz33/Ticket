<?php

namespace App\Filament\Tenant\Resources\Trainings\RelationManagers;

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

class TrainingOffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'Tarifs d’inscription';

    protected static ?string $modelLabel = 'tarif';

    protected static ?string $pluralModelLabel = 'tarifs';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tarif d’inscription')->schema([
                Hidden::make('offer_type')->default('standard')->dehydrated(),
                Hidden::make('sort_order')->default(0)->dehydrated(),
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('price_amount')->label('Prix (0 = gratuit)')->numeric()->default(0)->minValue(0)->required(),
                TextInput::make('currency_code')->label('Devise')->default(fn (): ?string => $this->getOwnerRecord()->currency_code ?: 'XOF')->maxLength(3),
                TextInput::make('quantity_total')->label('Places disponibles')->numeric()->minValue(1),
                TextInput::make('min_per_order')->label('Minimum par commande')->numeric()->default(1)->minValue(1),
                TextInput::make('max_per_order')->label('Maximum par commande')->numeric()->default(4)->minValue(1),
                DateTimePicker::make('sales_start_at')
                    ->label('Début des ventes')
                    ->maxDate(fn (): mixed => $this->getOwnerRecord()->starts_at),
                DateTimePicker::make('sales_end_at')
                    ->label('Fin des ventes')
                    ->helperText('La fin des ventes doit rester avant le début de la formation.')
                    ->maxDate(fn (): mixed => $this->getOwnerRecord()->starts_at),
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
                TextColumn::make('name')->label('Tarif')->searchable(),
                TextColumn::make('price_amount')->label('Prix')->numeric(),
                TextColumn::make('currency_code')->label('Devise')->badge(),
                TextColumn::make('quantity_sold')->label('Vendus')->numeric(),
                TextColumn::make('quantity_total')->label('Places')->numeric(),
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

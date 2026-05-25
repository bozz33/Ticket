<?php

namespace App\Filament\Tenant\Resources\Events\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventTicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Billetterie';

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'tickets';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->description('Minimum nécessaire pour vendre, limiter le stock et piloter les dates d’achat. La catégorie de ticket n’est plus utilisée.')
                    ->schema([
                        Hidden::make('ticket_type')->default('standard')->dehydrated(),
                        Hidden::make('currency_code')->default(fn (): ?string => $this->getOwnerRecord()->currency_code ?: 'XOF')->dehydrated(),
                        Hidden::make('sort_order')->default(0)->dehydrated(),
                        TextInput::make('name')->label('Nom du ticket')->required()->maxLength(255),
                        TextInput::make('price_amount')->label('Prix')->numeric()->required()->minValue(0)->default(0),
                        TextInput::make('quantity_total')->label('Nombre de tickets disponibles')->numeric()->required()->minValue(1),
                        TextInput::make('min_per_order')->label('Minimum par commande')->numeric()->default(1)->minValue(1),
                        TextInput::make('max_per_order')->label('Maximum par commande')->numeric()->minValue(1),
                        DateTimePicker::make('sales_start_at')
                            ->label('Début des ventes')
                            ->helperText('Date à partir de laquelle ce ticket peut être acheté.')
                            ->maxDate(fn (): mixed => $this->getOwnerRecord()->dates()->first()?->starts_at),
                        DateTimePicker::make('sales_end_at')
                            ->label('Fin des ventes')
                            ->helperText('Date limite d’achat de ce ticket. Elle doit rester avant la date de l’événement.')
                            ->maxDate(fn (): mixed => $this->getOwnerRecord()->dates()->first()?->starts_at),
                        Toggle::make('is_active')->label('Actif')->default(true),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Ticket')->searchable(),
                TextColumn::make('price_amount')->label('Prix')->numeric(),
                TextColumn::make('quantity_total')->label('Stock')->numeric(),
                TextColumn::make('quantity_sold')->label('Vendus')->numeric(),
                TextColumn::make('quantity_reserved')->label('Réservés')->numeric(),
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

<?php

namespace App\Filament\Tenant\Resources\Events\RelationManagers;

use App\Models\EventTicketCategory;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                Section::make('Ticket')->schema([
                    Select::make('ticket_category_id')
                        ->label('Catégorie de ticket')
                        ->helperText('Classement métier du ticket: Standard, VIP, Early Bird, Presse...')
                        ->options(fn (): array => EventTicketCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('name')->label('Nom')->required()->maxLength(255),
                    TextInput::make('code')->label('Code')->maxLength(100)->unique(ignoreRecord: true),
                    TextInput::make('ticket_type')->label('Type')->default('standard')->required()->maxLength(100),
                    TextInput::make('currency_code')->label('Devise')->default(fn (): ?string => $this->getOwnerRecord()->currency_code)->maxLength(3),
                    TextInput::make('price_amount')->label('Prix')->numeric()->default(0),
                    TextInput::make('quantity_total')->label('Stock total')->numeric(),
                    TextInput::make('quantity_sold')->label('Vendus')->numeric()->default(0)->disabled()->dehydrated(),
                    TextInput::make('quantity_reserved')->label('Réservés')->numeric()->default(0)->disabled()->dehydrated(),
                    TextInput::make('min_per_order')->label('Minimum par commande')->numeric()->default(1),
                    TextInput::make('max_per_order')->label('Maximum par commande')->numeric(),
                    TextInput::make('max_per_account')->label('Maximum par compte')->numeric(),
                    DateTimePicker::make('sales_start_at')->label('Début de vente'),
                    DateTimePicker::make('sales_end_at')->label('Fin de vente'),
                    Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
                    Toggle::make('is_active')->label('Actif')->default(true),
                    TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                    KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Ticket')->searchable(),
                TextColumn::make('ticketCategory.name')->label('Catégorie')->badge(),
                TextColumn::make('ticket_type')->label('Type')->badge(),
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

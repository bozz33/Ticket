<?php

namespace App\Filament\Tenant\Resources\EventTickets;

use App\Filament\Tenant\Resources\EventTickets\Pages\CreateEventTicket;
use App\Filament\Tenant\Resources\EventTickets\Pages\EditEventTicket;
use App\Filament\Tenant\Resources\EventTickets\Pages\ListEventTickets;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketCategory;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EventTicketResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = EventTicket::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static ?string $requiredTenantFeature = 'tenant.ticketing';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Billetterie événement';

    protected static ?string $modelLabel = 'Ticket événement';

    protected static ?string $pluralModelLabel = 'Billetterie événement';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Événement')->schema([
                    Select::make('event_id')
                        ->label('Événement')
                        ->options(fn (): array => Event::query()->orderBy('title')->pluck('title', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('ticket_category_id')
                        ->label('Catégorie de ticket')
                        ->helperText('Permet de regrouper les tickets côté billetterie: Standard, VIP, Early Bird, Presse...')
                        ->options(fn (): array => EventTicketCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                ]),
                Section::make('Ticket')->schema([
                    TextInput::make('name')->label('Nom')->required()->maxLength(255),
                    TextInput::make('code')->label('Code')->maxLength(100)->unique(ignoreRecord: true),
                    TextInput::make('ticket_type')->label('Type')->default('standard')->required()->maxLength(100),
                    TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3),
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

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Ticket')->searchable(),
                TextColumn::make('event.title')->label('Événement')->searchable(),
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
            ->recordActions([
                EditAction::make()
                    ->url(fn (EventTicket $record): string => static::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventTickets::route('/'),
            'create' => CreateEventTicket::route('/create'),
            'edit' => EditEventTicket::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return static::allows('update');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDeleteAny(): bool
    {
        return static::allows('update');
    }
}

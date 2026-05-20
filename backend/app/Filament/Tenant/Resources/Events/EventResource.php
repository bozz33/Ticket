<?php

namespace App\Filament\Tenant\Resources\Events;

use App\Enums\CategoryScope;
use App\Filament\Tenant\Resources\Events\Pages\CreateEvent;
use App\Filament\Tenant\Resources\Events\Pages\EditEvent;
use App\Filament\Tenant\Resources\Events\Pages\ListEvents;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventTicketCategory;
use App\Models\PublicStatus;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use UnitEnum;

class EventResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Event::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static ?string $requiredTenantFeature = 'tenant.ticketing';

    protected static string|UnitEnum|null $navigationGroup = 'Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Ticket / Événements';

    protected static ?string $modelLabel = 'Événement';

    protected static ?string $pluralModelLabel = 'Événements';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Événement')->schema([
                    Select::make('category_id')
                        ->label('Catégorie catalogue')
                        ->helperText('Sert aux filtres du catalogue public et peut rester vide si le classement n’est pas nécessaire.')
                        ->options(fn (): array => Category::query()->whereIn('module_scope', [CategoryScope::Global->value, CategoryScope::Event->value])->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Select::make('public_status_code')
                        ->label('Visibilité publique')
                        ->helperText('Détermine si la fiche apparaît publiquement ou reste en brouillon/archive.')
                        ->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())
                        ->default('published')
                        ->searchable()
                        ->preload(),
                    TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('summary')->label('Résumé')->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->label('Description')->rows(5)->columnSpanFull(),
                    TextInput::make('timezone')->label('Timezone')->default('UTC')->maxLength(100),
                    TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3),
                    Select::make('country_code')
                        ->label('Pays')
                        ->options(fn (): array => Country::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'iso2')->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('city_id', null)),
                    Select::make('city_id')
                        ->label('Ville')
                        ->options(fn ($get): array => City::query()
                            ->where('is_active', true)
                            ->when($get('country_code'), fn ($query, string $countryCode) => $query->whereHas('country', fn ($countryQuery) => $countryQuery->where('iso2', $countryCode)))
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('venue_name')->label('Lieu')->maxLength(255),
                    TextInput::make('venue_address')->label('Adresse du lieu')->maxLength(255),
                    FileUpload::make('cover_image_url')
                        ->label('Image mise en avant')
                        ->image()
                        ->disk('public')
                        ->directory('tenant/events/featured')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->columnSpanFull(),
                    Toggle::make('is_active')->label('Actif')->default(true),
                    DateTimePicker::make('published_at')
                        ->label('Publié le')
                        ->helperText('Renseigné automatiquement lors de la création.')
                        ->default(now())
                        ->disabled()
                        ->dehydrated(),
                ])->columns(2),
                Section::make('Dates')->schema([
                    DateTimePicker::make('meta.schedule.starts_at')
                        ->label('Début')
                        ->required()
                        ->default(fn (?Event $record): mixed => $record?->dates()->first()?->starts_at),
                    DateTimePicker::make('meta.schedule.ends_at')
                        ->label('Fin')
                        ->default(fn (?Event $record): mixed => $record?->dates()->first()?->ends_at),
                ])->columns(2),
                Section::make('Billetterie')->schema([
                    Repeater::make('tickets')
                        ->label('Tickets')
                        ->relationship('tickets')
                        ->schema([
                            Select::make('ticket_category_id')
                                ->label('Catégorie')
                                ->options(fn (): array => EventTicketCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload(),
                            TextInput::make('name')->label('Nom')->required()->maxLength(255),
                            TextInput::make('code')->label('Code')->maxLength(100)->unique(ignoreRecord: true),
                            TextInput::make('ticket_type')->label('Type')->default('standard')->required()->maxLength(100),
                            Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                            TextInput::make('currency_code')->label('Devise')->default(fn ($get): ?string => $get('../../currency_code') ?: 'XOF')->maxLength(3),
                            TextInput::make('price_amount')->label('Prix')->numeric()->default(0),
                            TextInput::make('quantity_total')->label('Stock total')->numeric(),
                            TextInput::make('min_per_order')->label('Minimum par commande')->numeric()->default(1),
                            TextInput::make('max_per_order')->label('Maximum par commande')->numeric(),
                            DateTimePicker::make('sales_start_at')->label('Début de vente'),
                            DateTimePicker::make('sales_end_at')->label('Fin de vente'),
                            Toggle::make('is_active')->label('Actif')->default(true),
                            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('category.name')->label('Catégorie')->badge(),
                TextColumn::make('public_status_code')
                    ->label('Statut public')
                    ->formatStateUsing(function (?string $state, Event $record): string {
                        $lastScheduledAt = $record->dates
                            ->map(fn ($date) => $date->ends_at ?? $date->starts_at)
                            ->filter()
                            ->max();

                        if ($lastScheduledAt && Carbon::parse($lastScheduledAt)->isPast()) {
                            return 'Terminé';
                        }

                        return (string) ($state ?: '—');
                    })
                    ->color(function (?string $state, Event $record): string {
                        $lastScheduledAt = $record->dates
                            ->map(fn ($date) => $date->ends_at ?? $date->starts_at)
                            ->filter()
                            ->max();

                        if ($lastScheduledAt && Carbon::parse($lastScheduledAt)->isPast()) {
                            return 'gray';
                        }

                        return $state === 'published' ? 'success' : 'warning';
                    })
                    ->badge(),
                TextColumn::make('venue_name')->label('Lieu'),
                TextColumn::make('currency_code')->label('Devise'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('published_at')->label('Publié le')->dateTime(),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('dates'))
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->url(fn (Event $record): string => static::getUrl('edit', ['record' => $record])),
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
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
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

<?php

namespace App\Filament\Tenant\Resources\Events\Schemas;

use App\Enums\CategoryScope;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Event;
use App\Models\PublicStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
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
                Section::make('Date et heure')->schema([
                    DateTimePicker::make('meta.schedule.starts_at')
                        ->label('Date et heure de l’événement')
                        ->helperText('Date unique retenue pour le calendrier public, les filtres, les passes et la billetterie.')
                        ->required()
                        ->default(fn (?Event $record): mixed => $record?->dates()->first()?->starts_at),
                ])->columns(1),
                Section::make('Billetterie')
                    ->description('Les dates de vente contrôlent uniquement la période d’achat du ticket. Elles ne remplacent pas les dates de l’événement.')
                    ->schema([
                    Repeater::make('tickets')
                        ->label('Tickets')
                        ->relationship('tickets')
                        ->schema([
                            Hidden::make('ticket_type')->default('standard')->dehydrated(),
                            Hidden::make('currency_code')->default(fn ($get): ?string => $get('../../currency_code') ?: 'XOF')->dehydrated(),
                            TextInput::make('name')->label('Nom')->required()->maxLength(255),
                            TextInput::make('price_amount')->label('Prix')->numeric()->required()->minValue(0)->default(0),
                            TextInput::make('quantity_total')->label('Nombre de tickets disponibles')->numeric()->required()->minValue(1),
                            TextInput::make('min_per_order')->label('Minimum par commande')->numeric()->minValue(1)->default(1),
                            TextInput::make('max_per_order')->label('Maximum par commande')->numeric()->minValue(1),
                            DateTimePicker::make('sales_start_at')->label('Début des ventes')->helperText('Date à partir de laquelle ce ticket peut être acheté.')->maxDate(fn ($get): mixed => $get('../../meta.schedule.starts_at')),
                            DateTimePicker::make('sales_end_at')->label('Fin des ventes')->helperText('Date limite d’achat de ce ticket. Elle doit rester avant la date de l’événement.')->maxDate(fn ($get): mixed => $get('../../meta.schedule.starts_at')),
                            Toggle::make('is_active')->label('Actif')->default(true),
                            Hidden::make('sort_order')->default(0)->dehydrated(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            ]);
    }
}

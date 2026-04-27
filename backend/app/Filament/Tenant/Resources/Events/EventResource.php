<?php

namespace App\Filament\Tenant\Resources\Events;

use App\Enums\CategoryScope;
use App\Filament\Tenant\Resources\Events\Pages\ManageEvents;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\PublicStatus;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
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
                        ->label('Catégorie')
                        ->options(fn (): array => Category::query()->whereIn('module_scope', [CategoryScope::Global->value, CategoryScope::Event->value])->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Select::make('public_status_code')
                        ->label('Statut public')
                        ->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())
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
                    TextInput::make('country_code')->label('Pays')->maxLength(2),
                    Select::make('city_id')
                        ->label('Ville')
                        ->options(fn (): array => City::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('venue_name')->label('Lieu')->maxLength(255),
                    TextInput::make('venue_address')->label('Adresse du lieu')->maxLength(255),
                    TextInput::make('cover_image_url')->label('Image couverture')->url()->maxLength(255),
                    Toggle::make('is_active')->label('Actif')->default(true),
                    DateTimePicker::make('published_at')->label('Publié le'),
                    KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
                ])->columns(2),
                Section::make('Dates')->schema([
                    Repeater::make('dates')
                        ->relationship('dates')
                        ->schema([
                            DateTimePicker::make('starts_at')->label('Début')->required(),
                            DateTimePicker::make('ends_at')->label('Fin'),
                            TextInput::make('timezone')->label('Timezone')->maxLength(100),
                            Toggle::make('is_all_day')->label('Toute la journée')->default(false),
                            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                            KeyValue::make('meta')->label('Meta')->columnSpanFull(),
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
                TextColumn::make('public_status_code')->label('Statut public')->badge(),
                TextColumn::make('venue_name')->label('Lieu'),
                TextColumn::make('currency_code')->label('Devise'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('published_at')->label('Publié le')->dateTime(),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
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
            'index' => ManageEvents::route('/'),
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

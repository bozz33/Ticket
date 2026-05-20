<?php

namespace App\Filament\Tenant\Resources\EventTicketCategories;

use App\Filament\Tenant\Resources\EventTicketCategories\Pages\CreateEventTicketCategory;
use App\Filament\Tenant\Resources\EventTicketCategories\Pages\EditEventTicketCategory;
use App\Filament\Tenant\Resources\EventTicketCategories\Pages\ListEventTicketCategories;
use App\Models\EventTicketCategory;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class EventTicketCategoryResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = EventTicketCategory::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static ?string $requiredTenantFeature = 'tenant.ticketing';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Catégories de tickets';

    protected static ?string $modelLabel = 'Catégorie de ticket';

    protected static ?string $pluralModelLabel = 'Catégories de tickets';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Catégorie')->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('code', Str::slug((string) $state))),
                    TextInput::make('code')->label('Code')->required()->maxLength(100)->unique(ignoreRecord: true),
                    ColorPicker::make('color')->label('Couleur'),
                    TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                    Toggle::make('is_active')->label('Actif')->default(true),
                    Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
                    KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Catégorie')->searchable(),
                TextColumn::make('code')->label('Code')->badge()->searchable(),
                ColorColumn::make('color')->label('Couleur'),
                TextColumn::make('tickets_count')->label('Tickets')->counts('tickets')->numeric(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make()
                    ->url(fn (EventTicketCategory $record): string => static::getUrl('edit', ['record' => $record])),
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
            'index' => ListEventTicketCategories::route('/'),
            'create' => CreateEventTicketCategory::route('/create'),
            'edit' => EditEventTicketCategory::route('/{record}/edit'),
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

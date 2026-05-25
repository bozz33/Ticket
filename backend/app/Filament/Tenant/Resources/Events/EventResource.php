<?php

namespace App\Filament\Tenant\Resources\Events;

use App\Filament\Tenant\Resources\Events\Pages\CreateEvent;
use App\Filament\Tenant\Resources\Events\Pages\EditEvent;
use App\Filament\Tenant\Resources\Events\Pages\ListEvents;
use App\Filament\Tenant\Resources\Events\RelationManagers\EventTicketsRelationManager;
use App\Filament\Tenant\Resources\Events\Schemas\EventForm;
use App\Filament\Tenant\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EventResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Event::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Création & modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Événements';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Événement';

    protected static ?string $pluralModelLabel = 'Événements';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
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
        return [
            EventTicketsRelationManager::class,
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

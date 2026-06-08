<?php

namespace App\Filament\Tenant\Resources\Trainings;

use App\Filament\Tenant\Resources\Trainings\Pages\CreateTraining;
use App\Filament\Tenant\Resources\Trainings\Pages\EditTraining;
use App\Filament\Tenant\Resources\Trainings\Pages\ListTrainings;
use App\Filament\Tenant\Resources\Trainings\RelationManagers\TrainingOffersRelationManager;
use App\Filament\Tenant\Resources\Trainings\Schemas\TrainingForm;
use App\Filament\Tenant\Resources\Trainings\Tables\TrainingsTable;
use App\Models\Training;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TrainingResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Training::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Création & modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Formations';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'Formation';

    protected static ?string $pluralModelLabel = 'Formations';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TrainingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainings::route('/'),
            'create' => CreateTraining::route('/create'),
            'edit' => EditTraining::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            TrainingOffersRelationManager::class,
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

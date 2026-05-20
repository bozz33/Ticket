<?php

namespace App\Filament\Tenant\Resources\CallForProjectSubmissions;

use App\Filament\Tenant\Resources\CallForProjectSubmissions\Pages\EditCallForProjectSubmission;
use App\Filament\Tenant\Resources\CallForProjectSubmissions\Pages\ListCallForProjectSubmissions;
use App\Filament\Tenant\Resources\CallForProjectSubmissions\Schemas\CallForProjectSubmissionForm;
use App\Filament\Tenant\Resources\CallForProjectSubmissions\Tables\CallForProjectSubmissionsTable;
use App\Models\CallForProjectSubmission;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CallForProjectSubmissionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = CallForProjectSubmission::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static ?string $requiredTenantFeature = 'tenant.calls_for_projects';

    protected static string|UnitEnum|null $navigationGroup = 'Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Candidatures';

    protected static ?string $modelLabel = 'Candidature';

    protected static ?string $pluralModelLabel = 'Candidatures';

    protected static ?string $recordTitleAttribute = 'applicant_name';

    protected static ?string $slug = 'call-for-project-submissions';

    public static function form(Schema $schema): Schema
    {
        return CallForProjectSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallForProjectSubmissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallForProjectSubmissions::route('/'),
            'edit' => EditCallForProjectSubmission::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

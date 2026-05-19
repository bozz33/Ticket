<?php

namespace App\Filament\Tenant\Resources\CallForProjectSubmissions;

use App\Filament\Tenant\Resources\CallForProjectSubmissions\Pages\EditCallForProjectSubmission;
use App\Filament\Tenant\Resources\CallForProjectSubmissions\Pages\ListCallForProjectSubmissions;
use App\Models\CallForProjectSubmission;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
        return $schema->components([
            Section::make('Candidature')->schema([
                TextInput::make('call_for_project_title')
                    ->label('Appel à projets')
                    ->formatStateUsing(fn (?CallForProjectSubmission $record): string => $record?->callForProject?->title ?? '—')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'submitted' => 'Soumise',
                        'in_review' => 'En revue',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Rejetée',
                    ])
                    ->required(),
                DateTimePicker::make('submitted_at')->label('Soumise le')->disabled()->dehydrated(false),
                DateTimePicker::make('reviewed_at')->label('Révisée le'),
                TextInput::make('applicant_name')->label('Nom')->disabled()->dehydrated(false),
                TextInput::make('applicant_email')->label('E-mail')->disabled()->dehydrated(false),
                TextInput::make('phone_country_code')->label('Indicatif')->disabled()->dehydrated(false),
                TextInput::make('phone_number')->label('Téléphone')->disabled()->dehydrated(false),
                TextInput::make('country_code')->label('Pays')->disabled()->dehydrated(false),
                TextInput::make('city_name')->label('Ville')->disabled()->dehydrated(false),
            ])->columns(2),
            Section::make('Réponses soumises')->schema([
                Textarea::make('answers')
                    ->label('Réponses')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string) $state)
                    ->rows(20)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Textarea::make('files')
                    ->label('Pièces jointes')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string) $state)
                    ->rows(12)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('callForProject'))
            ->columns([
                TextColumn::make('callForProject.title')->label('Appel à projets')->searchable(),
                TextColumn::make('applicant_name')->label('Candidat')->searchable(),
                TextColumn::make('applicant_email')->label('E-mail')->searchable(),
                TextColumn::make('city_name')->label('Ville')->toggleable(),
                TextColumn::make('country_code')->label('Pays')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('submitted_at')->label('Soumise le')->dateTime(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Voir')
                    ->url(fn (CallForProjectSubmission $record): string => static::getUrl('edit', ['record' => $record])),
            ]);
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

<?php

namespace App\Filament\Tenant\Resources\FormSubmissions;

use App\Filament\Tenant\Resources\FormSubmissions\Pages\EditFormSubmission;
use App\Filament\Tenant\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Models\FormSubmission;
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

class FormSubmissionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = FormSubmission::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Soumissions formulaires';

    protected static ?string $modelLabel = 'Soumission formulaire';

    protected static ?string $pluralModelLabel = 'Soumissions formulaires';

    protected static ?string $recordTitleAttribute = 'public_id';

    protected static ?string $slug = 'form-submissions';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Soumission')->schema([
                TextInput::make('form_title')
                    ->label('Formulaire')
                    ->formatStateUsing(fn (?FormSubmission $record): string => $record?->formDefinition?->title ?? '—')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('public_id')->label('ID public')->disabled()->dehydrated(false),
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
            ])->columns(2),
            Section::make('Données')->schema([
                Textarea::make('data')
                    ->label('Réponses')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string) $state)
                    ->rows(20)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Textarea::make('files')
                    ->label('Fichiers')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string) $state)
                    ->rows(10)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Textarea::make('meta')
                    ->label('Métadonnées')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : (string) $state)
                    ->rows(10)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('formDefinition'))
            ->columns([
                TextColumn::make('formDefinition.title')->label('Formulaire')->searchable(),
                TextColumn::make('public_id')->label('ID public')->searchable()->toggleable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('submitted_at')->label('Soumise le')->dateTime()->sortable(),
                TextColumn::make('reviewed_at')->label('Révisée le')->dateTime()->toggleable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Voir')
                    ->url(fn (FormSubmission $record): string => static::getUrl('edit', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'edit' => EditFormSubmission::route('/{record}/edit'),
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

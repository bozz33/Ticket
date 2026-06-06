<?php

namespace App\Filament\Tenant\Resources\CallsForProjects;

use App\Filament\Tenant\Resources\CallsForProjects\Pages\CreateCallForProject;
use App\Filament\Tenant\Resources\CallsForProjects\Pages\EditCallForProject;
use App\Filament\Tenant\Resources\CallsForProjects\Pages\ListCallForProjects;
use App\Filament\Tenant\Resources\CallsForProjects\RelationManagers\FormDefinitionRelationManager;
use App\Filament\Tenant\Resources\CallsForProjects\RelationManagers\PaymentOffersRelationManager;
use App\Filament\Tenant\Resources\CallsForProjects\RelationManagers\SubmissionsRelationManager;
use App\Filament\Tenant\Resources\CallsForProjects\Schemas\CallForProjectFormBuilderSchema;
use App\Models\CallForProject;
use App\Models\OrganizationProfile;
use App\Models\PublicStatus;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Illuminate\Support\Str;
use UnitEnum;

class CallForProjectResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = CallForProject::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Création & modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Appels à projets';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Appel à projets';

    protected static ?string $pluralModelLabel = 'Appels à projets';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'calls-for-projects';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appel à projets')->schema([
                Hidden::make('organization_profile_id')
                    ->default(fn (): ?int => OrganizationProfile::query()->value('id')),
                Select::make('public_status_code')->label('Visibilité publique')->helperText('Détermine si la fiche apparaît publiquement ou reste en brouillon/archive.')->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())->searchable()->preload()->default('published'),
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('summary')->label('Résumé')->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Description')->rows(5)->columnSpanFull(),
                FileUpload::make('meta.cover_image_url')
                    ->label('Image mise en avant')
                    ->helperText('Image principale affichée sur la fiche publique.')
                    ->image()
                    ->disk('public')
                    ->directory('tenant/calls-for-projects/featured')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->imageEditor()
                    ->required(fn (?CallForProject $record): bool => $record === null)
                    ->columnSpanFull(),
                DateTimePicker::make('meta.event_at')->label('Date et heure de l’activité')->helperText('Date publique du programme, atelier, jury ou rencontre associée à cet appel.')->required(),
                DateTimePicker::make('application_opens_at')->label('Ouverture des candidatures')->helperText('Date à partir de laquelle le formulaire devient soumettable.')->maxDate(fn ($get): mixed => $get('meta.event_at')),
                DateTimePicker::make('application_closes_at')->label('Clôture des candidatures')->helperText('Date limite de dépôt des dossiers. Elle doit rester avant la date de l’activité.')->maxDate(fn ($get): mixed => $get('meta.event_at')),
                Toggle::make('is_active')->label('Actif')->default(true),
                DateTimePicker::make('published_at')
                    ->label('Publié le')
                    ->helperText('Renseigné automatiquement lors de la création.')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(),
            ])->columns(2),
            Section::make('Paiement de candidature')->schema([
                Toggle::make('meta.application_payment.is_paid')
                    ->label('Candidature payante')
                    ->helperText('Si activé, configurez les tarifs / tickets de candidature dans l’onglet “Tarifs de candidature” après création.')
                    ->default(false)
                    ->columnSpan(1),
                Toggle::make('meta.application_payment.requires_receipt')
                    ->label('Demander un reçu de paiement')
                    ->helperText('À activer seulement si le paiement est fait hors plateforme et qu’un justificatif doit être téléversé.')
                    ->default(false)
                    ->columnSpan(1),
            ])->columns(2),
            Section::make('Formulaire de candidature')->schema([
                TextInput::make('meta.application_form.title')->label('Titre public')->placeholder('Formulaire de candidature')->maxLength(255)->columnSpan(2),
                Textarea::make('meta.application_form.description')->label('Description publique')->rows(2)->columnSpanFull(),
                TextInput::make('meta.application_form.submit_label')->label('Bouton d’envoi')->placeholder('Soumettre ma candidature')->maxLength(80),
                Textarea::make('meta.application_form.success_message')->label('Message de succès')->rows(2),
                CallForProjectFormBuilderSchema::make(),
            ])->columns(2),
            Section::make('Informations complémentaires publiques')
                ->description('Bloc optionnel pour enrichir la fiche publique: avantages, critères, documents et galerie.')
                ->collapsed()
                ->schema([
                    TagsInput::make('meta.highlights')->label('Points forts'),
                    TagsInput::make('meta.badges')->label('Badges'),
                    TagsInput::make('meta.conditions')->label('Conditions de participation')->columnSpanFull(),
                    TagsInput::make('meta.requiredDocuments')->label('Documents requis')->columnSpanFull(),
                    TagsInput::make('meta.gallery')->label('Galerie (URLs)')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->recordTitleAttribute('title')->modifyQueryUsing(fn (Builder $query) => $query->withCount('submissions'))->columns([
            TextColumn::make('title')->label('Titre')->searchable(),
            TextColumn::make('public_status_code')->label('Statut public')->badge(),
            TextColumn::make('submissions_count')->label('Candidatures')->badge(),
            TextColumn::make('meta.application_payment.is_paid')
                ->label('Paiement')
                ->formatStateUsing(fn ($state): string => $state ? 'Payant' : 'Gratuit')
                ->badge(),
            TextColumn::make('application_opens_at')->label('Ouverture')->dateTime(),
            TextColumn::make('application_closes_at')->label('Clôture')->dateTime(),
            IconColumn::make('is_active')->label('Actif')->boolean(),
            TextColumn::make('updated_at')->label('Mis à jour')->since(),
        ])->defaultSort('updated_at', 'desc')->recordActions([
            EditAction::make()
                ->url(fn (CallForProject $record): string => static::getUrl('edit', ['record' => $record])),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallForProjects::route('/'),
            'create' => CreateCallForProject::route('/create'),
            'edit' => EditCallForProject::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PaymentOffersRelationManager::class,
            FormDefinitionRelationManager::class,
            SubmissionsRelationManager::class,
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

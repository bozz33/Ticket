<?php

namespace App\Filament\Tenant\Resources\CallsForProjects;

use App\Enums\CategoryScope;
use App\Filament\Tenant\Resources\CallsForProjects\Pages\CreateCallForProject;
use App\Filament\Tenant\Resources\CallsForProjects\Pages\EditCallForProject;
use App\Filament\Tenant\Resources\CallsForProjects\Pages\ListCallForProjects;
use App\Filament\Tenant\Resources\CallsForProjects\RelationManagers\SubmissionsRelationManager;
use App\Filament\Tenant\Resources\FormDefinitions\FormDefinitionResource;
use App\Models\CallForProject;
use App\Models\Category;
use App\Models\OrganizationProfile;
use App\Models\PublicStatus;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

    protected static ?string $requiredTenantFeature = 'tenant.calls_for_projects';

    protected static string|UnitEnum|null $navigationGroup = 'Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Appels à projets';

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
                Select::make('category_id')->label('Catégorie catalogue')->helperText('Optionnel. Sert aux filtres du catalogue public, pas seulement à la billetterie.')->options(fn (): array => Category::query()->whereIn('module_scope', [CategoryScope::Global->value, CategoryScope::Call->value])->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                Select::make('public_status_code')->label('Visibilité publique')->helperText('Détermine si la fiche apparaît publiquement ou reste en brouillon/archive.')->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())->searchable()->preload()->default('published'),
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('summary')->label('Résumé')->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Description')->rows(5)->columnSpanFull(),
                TextInput::make('meta.cover_image_url')->label('Image couverture')->url()->maxLength(255),
                DateTimePicker::make('application_opens_at')->label('Ouverture candidatures'),
                DateTimePicker::make('application_closes_at')->label('Clôture candidatures'),
                Toggle::make('is_active')->label('Actif')->default(true),
                DateTimePicker::make('published_at')
                    ->label('Publié le')
                    ->helperText('Renseigné automatiquement lors de la création.')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(),
            ])->columns(2),
            Section::make('Contenu public')->schema([
                TagsInput::make('meta.highlights')->label('Points forts'),
                TagsInput::make('meta.badges')->label('Badges'),
                TagsInput::make('meta.conditions')->label('Conditions de participation')->columnSpanFull(),
                TagsInput::make('meta.requiredDocuments')->label('Documents requis')->columnSpanFull(),
                TagsInput::make('meta.gallery')->label('Galerie (URLs)')->columnSpanFull(),
            ])->columns(2),
            Section::make('Parcours de candidature')->schema([
                TextInput::make('meta.application_form.title')->label('Titre du formulaire')->maxLength(255),
                Textarea::make('meta.application_form.description')->label('Description du formulaire')->rows(3)->columnSpanFull(),
                TextInput::make('meta.application_form.submit_label')->label('Libellé du bouton')->maxLength(120),
                Textarea::make('meta.application_form.success_message')->label('Message de succès')->rows(3)->columnSpanFull(),
                Toggle::make('meta.application_payment.requires_receipt')->label('Exiger un reçu de paiement')->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->recordTitleAttribute('title')->modifyQueryUsing(fn (Builder $query) => $query->withCount('submissions'))->columns([
            TextColumn::make('title')->label('Titre')->searchable(),
            TextColumn::make('category.name')->label('Catégorie')->badge(),
            TextColumn::make('public_status_code')->label('Statut public')->badge(),
            TextColumn::make('submissions_count')->label('Candidatures')->badge(),
            TextColumn::make('application_opens_at')->label('Ouverture')->dateTime(),
            TextColumn::make('application_closes_at')->label('Clôture')->dateTime(),
            IconColumn::make('is_active')->label('Actif')->boolean(),
            TextColumn::make('updated_at')->label('Mis à jour')->since(),
        ])->defaultSort('updated_at', 'desc')->recordActions([
            Action::make('formDefinition')
                ->label('Formulaire')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(fn (CallForProject $record): string => static::formDefinitionUrl($record)),
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

    protected static function formDefinitionUrl(CallForProject $record): string
    {
        $formDefinition = $record->formDefinition;

        if ($formDefinition !== null) {
            return FormDefinitionResource::getUrl('edit', ['record' => $formDefinition]);
        }

        return FormDefinitionResource::getUrl('create', [
            'owner_type' => CallForProject::class,
            'owner_id' => $record->getKey(),
        ]);
    }
}

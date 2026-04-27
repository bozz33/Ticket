<?php

namespace App\Filament\Tenant\Resources\CallsForProjects;

use App\Enums\CategoryScope;
use App\Filament\Tenant\Resources\CallsForProjects\Pages\ManageCallForProjects;
use App\Models\CallForProject;
use App\Models\Category;
use App\Models\PublicStatus;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appel à projets')->schema([
                Select::make('category_id')->label('Catégorie')->options(fn (): array => Category::query()->whereIn('module_scope', [CategoryScope::Global->value, CategoryScope::Call->value])->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                Select::make('public_status_code')->label('Statut public')->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())->searchable()->preload(),
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('summary')->label('Résumé')->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Description')->rows(5)->columnSpanFull(),
                DateTimePicker::make('application_opens_at')->label('Ouverture candidatures'),
                DateTimePicker::make('application_closes_at')->label('Clôture candidatures'),
                Toggle::make('is_active')->label('Actif')->default(true),
                DateTimePicker::make('published_at')->label('Publié le'),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->recordTitleAttribute('title')->columns([
            TextColumn::make('title')->label('Titre')->searchable(),
            TextColumn::make('category.name')->label('Catégorie')->badge(),
            TextColumn::make('public_status_code')->label('Statut public')->badge(),
            TextColumn::make('application_opens_at')->label('Ouverture')->dateTime(),
            TextColumn::make('application_closes_at')->label('Clôture')->dateTime(),
            IconColumn::make('is_active')->label('Actif')->boolean(),
            TextColumn::make('updated_at')->label('Mis à jour')->since(),
        ])->defaultSort('updated_at', 'desc')->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCallForProjects::route('/')];
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

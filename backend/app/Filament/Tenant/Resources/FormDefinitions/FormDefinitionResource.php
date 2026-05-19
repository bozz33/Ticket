<?php

namespace App\Filament\Tenant\Resources\FormDefinitions;

use App\Filament\Tenant\Resources\FormDefinitions\Pages\CreateFormDefinition;
use App\Filament\Tenant\Resources\FormDefinitions\Pages\EditFormDefinition;
use App\Filament\Tenant\Resources\FormDefinitions\Pages\ListFormDefinitions;
use App\Models\CallForProject;
use App\Models\Event;
use App\Models\FormDefinition;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FormDefinitionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = FormDefinition::class;

    protected static ?string $permissionPrefix = 'tenant.catalog';

    protected static string|UnitEnum|null $navigationGroup = 'Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Form builder';

    protected static ?string $modelLabel = 'Formulaire';

    protected static ?string $pluralModelLabel = 'Form builder';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identification')->schema([
                TextInput::make('name')->label('Nom interne')->required()->maxLength(255),
                TextInput::make('title')->label('Titre public')->required()->maxLength(255),
                Select::make('status')->label('Statut')->options([
                    'draft' => 'Brouillon',
                    'published' => 'Publié',
                    'archived' => 'Archivé',
                ])->default('draft')->required(),
                Select::make('owner_type')
                    ->label('Module lié')
                    ->options([
                        CallForProject::class => 'Appel à projets',
                        Event::class => 'Événement',
                    ])
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('owner_id', null)),
                Select::make('owner_id')
                    ->label('Contenu lié')
                    ->options(fn ($get): array => static::ownerOptions((string) $get('owner_type')))
                    ->searchable()
                    ->preload(),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                TextInput::make('submit_label')->label('Libellé du bouton')->default('Envoyer')->maxLength(80),
                Textarea::make('success_message')->label('Message de succès')->rows(3)->columnSpanFull(),
            ])->columns(2),
            Section::make('Champs')->schema([
                Builder::make('schema.fields')
                    ->label('Champs du formulaire')
                    ->blocks(static::fieldBlocks())
                    ->columnSpanFull(),
            ]),
            Section::make('Réglages avancés')->schema([
                KeyValue::make('settings')->label('Réglages')->columnSpanFull(),
                KeyValue::make('validation_schema')->label('Validation avancée')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('title')->label('Titre public')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('owner_type')->label('Module')->formatStateUsing(fn (?string $state): string => static::ownerTypeLabel($state))->badge(),
                TextColumn::make('submissions_count')->label('Soumissions')->counts('submissions'),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make()->url(fn (FormDefinition $record): string => static::getUrl('edit', ['record' => $record])),
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
            'index' => ListFormDefinitions::route('/'),
            'create' => CreateFormDefinition::route('/create'),
            'edit' => EditFormDefinition::route('/{record}/edit'),
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

    protected static function fieldBlocks(): array
    {
        return [
            Builder\Block::make('text')->label('Texte')->schema(static::commonFieldSchema('text')),
            Builder\Block::make('textarea')->label('Zone de texte')->schema(static::commonFieldSchema('textarea')),
            Builder\Block::make('email')->label('Email')->schema(static::commonFieldSchema('email')),
            Builder\Block::make('number')->label('Nombre')->schema(static::commonFieldSchema('number')),
            Builder\Block::make('date')->label('Date')->schema(static::commonFieldSchema('date')),
            Builder\Block::make('select')->label('Liste')->schema(array_merge(static::commonFieldSchema('select'), [KeyValue::make('options')->label('Options')])),
            Builder\Block::make('checkbox')->label('Case')->schema(static::commonFieldSchema('checkbox')),
            Builder\Block::make('consent')->label('Consentement')->schema(static::commonFieldSchema('consent')),
            Builder\Block::make('section')->label('Section')->schema([
                TextInput::make('key')->label('Clé')->required()->maxLength(80),
                TextInput::make('label')->label('Titre')->required()->maxLength(255),
                Textarea::make('help_text')->label('Texte')->rows(3)->columnSpanFull(),
            ]),
        ];
    }

    protected static function commonFieldSchema(string $type): array
    {
        return [
            TextInput::make('key')->label('Clé')->required()->maxLength(80),
            TextInput::make('label')->label('Libellé')->required()->maxLength(255),
            Textarea::make('help_text')->label('Aide')->rows(2)->columnSpanFull(),
            Toggle::make('required')->label('Obligatoire')->default(false),
            Toggle::make('visible')->label('Visible')->default(true),
            TextInput::make('type')->default($type)->hidden()->dehydrated(),
        ];
    }

    protected static function ownerOptions(string $type): array
    {
        return match ($type) {
            CallForProject::class => CallForProject::query()->orderBy('title')->pluck('title', 'id')->all(),
            Event::class => Event::query()->orderBy('title')->pluck('title', 'id')->all(),
            default => [],
        };
    }

    protected static function ownerTypeLabel(?string $type): string
    {
        return match ($type) {
            CallForProject::class => 'Appel à projets',
            Event::class => 'Événement',
            default => 'Global',
        };
    }
}

<?php

namespace App\Filament\Tenant\Resources\Forms;

use App\Filament\Tenant\Resources\CallsForProjects\Schemas\CallForProjectFormBuilderSchema;
use App\Filament\Tenant\Resources\Forms\Pages\CreateFormDefinition;
use App\Filament\Tenant\Resources\Forms\Pages\EditFormDefinition;
use App\Filament\Tenant\Resources\Forms\Pages\ListFormDefinitions;
use App\Filament\Tenant\Resources\Forms\RelationManagers\FormSubmissionsRelationManager;
use App\Models\FormDefinition;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
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
use UnitEnum;

class FormDefinitionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = FormDefinition::class;

    protected static ?string $permissionPrefix = 'tenant.forms';

    protected static string|UnitEnum|null $navigationGroup = 'Création & modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Formulaires';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'Formulaire';

    protected static ?string $pluralModelLabel = 'Formulaires';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations générales')->schema([
                TextInput::make('name')
                    ->label('Nom interne')
                    ->required()
                    ->maxLength(255),
                TextInput::make('title')
                    ->label('Titre public')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'published' => 'Publié',
                        'archived' => 'Archivé',
                    ])
                    ->default('draft')
                    ->required(),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('submit_label')
                    ->label('Libellé du bouton d\'envoi')
                    ->default('Envoyer')
                    ->maxLength(80),
                Textarea::make('success_message')
                    ->label('Message après soumission')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Champs du formulaire')->schema([
                CallForProjectFormBuilderSchema::make('schema.fields'),
            ]),

            Section::make('Notifications')->schema([
                Toggle::make('settings.notify_owner_on_submission')
                    ->label('M\'avertir par e-mail à chaque nouvelle soumission')
                    ->default(true),
                Toggle::make('settings.send_confirmation_email')
                    ->label('Envoyer un e-mail de confirmation au soumissionnaire')
                    ->helperText('Nécessite un champ de type "E-mail" dans le formulaire.')
                    ->default(false),
            ]),

            Section::make('Réglages avancés')->schema([
                KeyValue::make('settings')
                    ->label('Réglages personnalisés')
                    ->columnSpanFull(),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('submissions_count')
                    ->label('Soumissions')
                    ->counts('submissions')
                    ->sortable(),
                TextColumn::make('owner_type')
                    ->label('Propriétaire')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Dupliquer')
                    ->beforeReplicaSaved(function (FormDefinition $replica): void {
                        $replica->name = $replica->name.' (copie)';
                        $replica->title = $replica->title.' (copie)';
                        $replica->status = 'draft';
                        $replica->owner_type = null;
                        $replica->owner_id = null;
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            FormSubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormDefinitions::route('/'),
            'create' => CreateFormDefinition::route('/create'),
            'edit' => EditFormDefinition::route('/{record}/edit'),
        ];
    }
}

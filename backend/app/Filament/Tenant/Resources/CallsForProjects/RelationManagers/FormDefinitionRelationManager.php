<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\RelationManagers;

use App\Filament\Tenant\Resources\CallsForProjects\Schemas\CallForProjectFormBuilderSchema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormDefinitionRelationManager extends RelationManager
{
    protected static string $relationship = 'formDefinition';

    protected static ?string $title = 'Formulaire de candidature';

    protected static ?string $modelLabel = 'formulaire';

    protected static ?string $pluralModelLabel = 'formulaire';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Formulaire affiché sur le front')->schema([
                TextInput::make('name')->label('Nom interne')->required()->maxLength(255),
                TextInput::make('title')->label('Titre public')->required()->maxLength(255),
                Select::make('status')->label('Statut')->options([
                    'draft' => 'Brouillon',
                    'published' => 'Publié',
                    'archived' => 'Archivé',
                ])->default('draft')->required(),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                TextInput::make('submit_label')->label('Libellé du bouton')->default('Envoyer')->maxLength(80),
                Textarea::make('success_message')->label('Message de succès')->rows(3)->columnSpanFull(),
            ])->columns(2),
            Section::make('Champs')->schema([
                CallForProjectFormBuilderSchema::make('schema.fields'),
            ]),
            Section::make('Réglages avancés')->schema([
                KeyValue::make('settings')->label('Réglages')->columnSpanFull(),
                KeyValue::make('validation_schema')->label('Validation avancée')->columnSpanFull(),
            ])->collapsed(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titre public')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('submissions_count')->label('Soumissions')->counts('submissions'),
                TextColumn::make('updated_at')->label('Mis à jour')->since(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }

}

<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Builder::make('schema.fields')
                    ->label('Champs du formulaire')
                    ->blocks(static::fieldBlocks())
                    ->columnSpanFull(),
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

    protected static function fieldBlocks(): array
    {
        return [
            Builder\Block::make('text')->label('Texte')->schema(static::commonFieldSchema()),
            Builder\Block::make('textarea')->label('Zone de texte')->schema(static::commonFieldSchema()),
            Builder\Block::make('email')->label('Email')->schema(static::commonFieldSchema()),
            Builder\Block::make('number')->label('Nombre')->schema(static::commonFieldSchema()),
            Builder\Block::make('date')->label('Date')->schema(static::commonFieldSchema()),
            Builder\Block::make('select')->label('Liste')->schema(array_merge(static::commonFieldSchema(), [KeyValue::make('options')->label('Options')])),
            Builder\Block::make('checkbox')->label('Case')->schema(static::commonFieldSchema()),
            Builder\Block::make('consent')->label('Consentement')->schema(static::commonFieldSchema()),
            Builder\Block::make('section')->label('Section')->schema([
                TextInput::make('key')->label('Clé')->required()->maxLength(80),
                TextInput::make('label')->label('Titre')->required()->maxLength(255),
                Textarea::make('help_text')->label('Texte')->rows(3)->columnSpanFull(),
            ]),
        ];
    }

    protected static function commonFieldSchema(): array
    {
        return [
            TextInput::make('key')->label('Clé')->required()->maxLength(80),
            TextInput::make('label')->label('Libellé')->required()->maxLength(255),
            Textarea::make('help_text')->label('Aide')->rows(2)->columnSpanFull(),
            Toggle::make('required')->label('Obligatoire')->default(false),
            TextInput::make('placeholder')->label('Placeholder')->maxLength(255),
        ];
    }
}

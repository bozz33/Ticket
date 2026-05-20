<?php

namespace App\Filament\Tenant\Resources\CallForProjectSubmissions\Schemas;

use App\Models\CallForProjectSubmission;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CallForProjectSubmissionForm
{
    public static function configure(Schema $schema): Schema
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
            Section::make('Évaluation')->schema([
                TextInput::make('meta.review_score')
                    ->label('Score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                Textarea::make('meta.review_notes')
                    ->label('Notes internes')
                    ->rows(6)
                    ->columnSpanFull(),
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
}

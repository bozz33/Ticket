<?php

namespace App\Filament\Tenant\Resources\CrowdfundingCampaigns\Schemas;

use App\Models\CrowdfundingCampaign;
use App\Models\PublicStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CrowdfundingCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campagne')->schema([
                Select::make('public_status_code')->label('Visibilité publique')->helperText('Détermine si la fiche apparaît publiquement ou reste en brouillon/archive.')->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())->default('published')->searchable()->preload(),
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('summary')->label('Résumé')->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Description')->rows(5)->columnSpanFull(),
                FileUpload::make('meta.cover_image_url')
                    ->label('Image mise en avant')
                    ->helperText('Image principale affichée sur la fiche publique de la campagne.')
                    ->image()
                    ->disk('public')
                    ->directory('tenant/crowdfunding/featured')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->imageEditor()
                    ->required(fn (?CrowdfundingCampaign $record): bool => $record === null)
                    ->columnSpanFull(),
                TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3),
                TextInput::make('target_amount')->label('Objectif de collecte')->numeric()->required()->minValue(1),
                TextInput::make('raised_amount')->label('Montant collecté')->numeric()->default(0)->disabled()->dehydrated(),
                DateTimePicker::make('meta.event_at')->label('Date et heure de l’activité')->helperText('Date publique liée à la campagne ou à la remise/rencontre financée.')->required(),
                DateTimePicker::make('starts_at')->label('Début de collecte')->maxDate(fn ($get): mixed => $get('meta.event_at')),
                DateTimePicker::make('ends_at')->label('Fin de collecte')->helperText('La collecte doit se terminer avant la date de l’activité.')->maxDate(fn ($get): mixed => $get('meta.event_at')),
                Toggle::make('is_active')->label('Actif')->default(true),
                DateTimePicker::make('published_at')
                    ->label('Publié le')
                    ->helperText('Renseigné automatiquement lors de la création.')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(),
            ])->columns(2),
        ]);
    }
}

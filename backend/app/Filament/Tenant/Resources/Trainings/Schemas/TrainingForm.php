<?php

namespace App\Filament\Tenant\Resources\Trainings\Schemas;

use App\Enums\CategoryScope;
use App\Models\Category;
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

class TrainingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Formation')->schema([
                Select::make('public_status_code')->label('Visibilité publique')->helperText('Détermine si la fiche apparaît publiquement ou reste en brouillon/archive.')->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())->default('published')->searchable()->preload(),
                Select::make('category_id')->label('Catégorie')->options(fn (): array => Category::query()->whereIn('module_scope', [CategoryScope::Global->value, CategoryScope::Training->value])->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('summary')->label('Résumé')->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Description')->rows(5)->columnSpanFull(),
                FileUpload::make('meta.cover_image_url')
                    ->label('Image mise en avant')
                    ->helperText('Image principale affichée sur la fiche publique de la formation.')
                    ->image()
                    ->disk('public')
                    ->directory('tenant/trainings/featured')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->imageEditor()
                    ->columnSpanFull(),
                TextInput::make('venue_name')->label('Lieu'),
                TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3),
                DateTimePicker::make('starts_at')->label('Début de la formation')->required(),
                DateTimePicker::make('ends_at')->label('Fin de la formation')->helperText('La fin doit être après le début.')->after('starts_at'),
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

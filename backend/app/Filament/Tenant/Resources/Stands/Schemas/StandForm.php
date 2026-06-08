<?php

namespace App\Filament\Tenant\Resources\Stands\Schemas;

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

class StandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Stand')->schema([
                Select::make('public_status_code')->label('Visibilité publique')->helperText('Détermine si la fiche apparaît publiquement ou reste en brouillon/archive.')->options(fn (): array => PublicStatus::query()->orderBy('sort_order')->pluck('name', 'code')->all())->default('published')->searchable()->preload(),
                Select::make('category_id')->label('Catégorie')->options(fn (): array => Category::query()->whereIn('module_scope', [CategoryScope::Global->value, CategoryScope::Stand->value])->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                TextInput::make('name')->label('Nom')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('summary')->label('Résumé')->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Description')->rows(5)->columnSpanFull(),
                FileUpload::make('meta.cover_image_url')
                    ->label('Image mise en avant')
                    ->helperText('Image principale affichée sur la fiche publique du stand.')
                    ->image()
                    ->disk('public')
                    ->directory('tenant/stands/featured')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->imageEditor()
                    ->columnSpanFull(),
                TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3),
                TextInput::make('price_amount')->label('Prix (0 = gratuit)')->numeric()->default(0)->minValue(0)->required(),
                TextInput::make('quantity_available')->label('Stands disponibles')->numeric()->minValue(1),
                DateTimePicker::make('meta.event_at')->label('Date et heure de l’activité')->helperText('Date publique du salon/événement où se tient le stand.'),
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

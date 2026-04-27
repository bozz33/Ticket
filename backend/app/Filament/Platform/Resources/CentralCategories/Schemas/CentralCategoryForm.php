<?php

namespace App\Filament\Platform\Resources\CentralCategories\Schemas;

use App\Enums\CategoryScope;
use App\Models\CentralCategory;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CentralCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Catégorie')->schema([
                Select::make('parent_id')->label('Parent')->options(fn (): array => CentralCategory::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                TextInput::make('name')->label('Nom')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                Select::make('module_scope')->label('Portée module')->options(array_combine(CategoryScope::values(), CategoryScope::values()))->required()->default(CategoryScope::Global->value),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                Toggle::make('is_active')->label('Actif')->default(true),
                KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

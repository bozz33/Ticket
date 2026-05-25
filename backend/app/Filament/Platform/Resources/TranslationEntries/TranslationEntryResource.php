<?php

namespace App\Filament\Platform\Resources\TranslationEntries;

use App\Filament\Platform\Resources\TranslationEntries\Pages\ListTranslationEntries;
use App\Models\Language;
use App\Models\TranslationEntry;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TranslationEntryResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = TranslationEntry::class;

    protected static ?string $permissionPrefix = 'platform.references';

    protected static string|UnitEnum|null $navigationGroup = 'Langues & traductions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationLabel = 'Traductions';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'traduction';

    protected static ?string $pluralModelLabel = 'Traductions';

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Traduction clé / valeur')->schema([
                Select::make('language_id')
                    ->label('Langue')
                    ->options(fn (): array => Language::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2),
                TextInput::make('group')
                    ->label('Groupe')
                    ->default('front')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(2),
                TextInput::make('key')
                    ->label('Clé')
                    ->placeholder('nav.account')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Textarea::make('value')
                    ->label('Texte traduit')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(1),
                KeyValue::make('meta')
                    ->label('Métadonnées')
                    ->columnSpanFull(),
            ])->columns(6),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('language'))
            ->columns([
                TextColumn::make('language.code')->label('Langue')->badge()->sortable(),
                TextColumn::make('group')->label('Groupe')->badge()->searchable(),
                TextColumn::make('key')->label('Clé')->searchable(),
                TextColumn::make('value')->label('Texte')->limit(80)->searchable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('key')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslationEntries::route('/'),
        ];
    }
}

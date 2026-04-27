<?php

namespace App\Filament\Platform\Resources\SeoSettings;

use App\Filament\Platform\Resources\SeoSettings\Pages\CreateSeoSetting;
use App\Filament\Platform\Resources\SeoSettings\Pages\EditSeoSetting;
use App\Filament\Platform\Resources\SeoSettings\Pages\ListSeoSettings;
use App\Models\PlatformSetting;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SeoSettingResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformSetting::class;

    protected static ?string $permissionPrefix = 'platform.platform_settings';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'SEO';

    protected static ?string $modelLabel = 'Réglage SEO';

    protected static ?string $pluralModelLabel = 'SEO';

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('SEO plateforme')->schema([
                    Hidden::make('group')->default('seo')->dehydrated(),
                    TextInput::make('key')
                        ->label('Clé')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true)
                        ->helperText('Exemples : meta_title, meta_description, robots, og, twitter.')
                        ->columnSpan(4),
                    TextInput::make('type')
                        ->label('Type')
                        ->default('json')
                        ->required()
                        ->maxLength(50)
                        ->columnSpan(1),
                    Toggle::make('is_public')
                        ->label('Public')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),
                    KeyValue::make('value')
                        ->label('Valeur')
                        ->columnSpanFull(),
                ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Clé')->searchable(),
                TextColumn::make('type')->label('Type')->badge(),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime()->sortable(),
            ])
            ->defaultSort('key')
            ->recordActions([
                EditAction::make(),
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
            'index' => ListSeoSettings::route('/'),
            'create' => CreateSeoSetting::route('/create'),
            'edit' => EditSeoSetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('group', 'seo');
    }
}

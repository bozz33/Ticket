<?php

namespace App\Filament\Platform\Resources\FeatureFlags;

use App\Filament\Platform\Resources\FeatureFlags\Pages\CreateFeatureFlag;
use App\Filament\Platform\Resources\FeatureFlags\Pages\EditFeatureFlag;
use App\Filament\Platform\Resources\FeatureFlags\Pages\ListFeatureFlags;
use App\Models\FeatureFlag;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FeatureFlagResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = FeatureFlag::class;

    protected static ?string $permissionPrefix = 'platform.feature_flags';

    protected static string|UnitEnum|null $navigationGroup = 'Offre & Modules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Modules';

    protected static ?string $modelLabel = 'Module';

    protected static ?string $pluralModelLabel = 'Modules';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module')->schema([
                    TextInput::make('code')->label('Code')->required()->maxLength(120)->columnSpan(2),
                    TextInput::make('name')->label('Nom')->required()->maxLength(255)->columnSpan(2),
                    TextInput::make('module')->label('Famille')->maxLength(120)->columnSpan(2),
                    Toggle::make('default_enabled')->label('Activé globalement')->default(false)->inline(false)->columnSpan(2),
                    Toggle::make('requires_subscription')->label('Nécessite un abonnement')->default(false)->inline(false)->columnSpan(2),
                    Toggle::make('is_public')->label('Public')->default(false)->inline(false)->columnSpan(1),
                    Toggle::make('is_active')->label('Actif')->default(true)->inline(false)->columnSpan(1),
                    Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                    KeyValue::make('meta')->label('Métadonnées')->columnSpanFull(),
                ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('code')->label('Code')->searchable(),
                TextColumn::make('module')->label('Famille')->badge(),
                IconColumn::make('default_enabled')->label('Global')->boolean(),
                IconColumn::make('requires_subscription')->label('Abonnement')->boolean(),
                IconColumn::make('is_public')->label('Public')->boolean(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                //
            ])
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
            'index' => ListFeatureFlags::route('/'),
            'create' => CreateFeatureFlag::route('/create'),
            'edit' => EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}

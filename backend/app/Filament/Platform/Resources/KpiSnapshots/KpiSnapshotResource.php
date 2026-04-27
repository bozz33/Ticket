<?php

namespace App\Filament\Platform\Resources\KpiSnapshots;

use App\Filament\Platform\Resources\KpiSnapshots\Pages\ManageKpiSnapshots;
use App\Models\KpiSnapshot;
use App\Models\Tenant;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class KpiSnapshotResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = KpiSnapshot::class;

    protected static ?string $permissionPrefix = 'platform.kpi_snapshots';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $recordTitleAttribute = 'scope';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Snapshot KPI')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    TextInput::make('scope')->label('Périmètre')->required()->maxLength(255),
                    DatePicker::make('snapshot_date')->label('Date snapshot')->required(),
                    KeyValue::make('metrics')->label('Métriques')->columnSpanFull(),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('scope')
            ->columns([
                TextColumn::make('scope')
                    ->label('Périmètre')
                    ->searchable(),
                TextColumn::make('tenant.name')->label('Tenant'),
                TextColumn::make('snapshot_date')->label('Date')->date(),
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
            'index' => ManageKpiSnapshots::route('/'),
        ];
    }
}

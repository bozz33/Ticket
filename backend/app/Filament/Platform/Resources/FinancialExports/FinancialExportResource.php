<?php

namespace App\Filament\Platform\Resources\FinancialExports;

use App\Filament\Platform\Resources\FinancialExports\Pages\ManageFinancialExports;
use App\Models\FinancialExport;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FinancialExportResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = FinancialExport::class;

    protected static ?string $permissionPrefix = 'platform.financial_exports';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $recordTitleAttribute = 'export_type';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Export')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Select::make('platform_user_id')->label('Demandé par')->options(fn (): array => PlatformUser::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Select::make('export_type')->label('Type')->options(['transactions' => 'Transactions', 'settlements' => 'Settlements', 'incidents' => 'Incidents', 'kpis' => 'KPIs'])->required(),
                    Select::make('format')->label('Format')->options(['csv' => 'CSV', 'xlsx' => 'XLSX', 'json' => 'JSON'])->default('csv')->required(),
                    Select::make('status')->label('Statut')->options(['pending' => 'En attente', 'processing' => 'En cours', 'ready' => 'Prêt', 'failed' => 'Échec'])->default('pending')->required(),
                    TextInput::make('file_path')->label('Chemin fichier')->maxLength(255),
                    DateTimePicker::make('generated_at')->label('Généré le'),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('export_type')
            ->columns([
                TextColumn::make('export_type')
                    ->label('Type')
                    ->searchable(),
                TextColumn::make('tenant.name')->label('Tenant'),
                TextColumn::make('requestedBy.name')->label('Demandé par'),
                TextColumn::make('format')->label('Format')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('generated_at')->label('Généré')->dateTime(),
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
            'index' => ManageFinancialExports::route('/'),
        ];
    }
}

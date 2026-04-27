<?php

namespace App\Filament\Platform\Resources\ReconciliationLogs;

use App\Filament\Platform\Resources\ReconciliationLogs\Pages\ManageReconciliationLogs;
use App\Models\PaymentGateway;
use App\Models\PlatformTransaction;
use App\Models\ReconciliationLog;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class ReconciliationLogResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = ReconciliationLog::class;

    protected static ?string $permissionPrefix = 'platform.reconciliation_logs';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $recordTitleAttribute = 'scope';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rapprochement')->schema([
                    Select::make('payment_gateway_id')->label('Gateway')->options(fn (): array => PaymentGateway::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Select::make('platform_transaction_id')->label('Transaction')->options(fn (): array => PlatformTransaction::query()->orderByDesc('id')->pluck('transaction_reference', 'id')->all())->searchable()->preload(),
                    Select::make('status')->label('Statut')->options(['pending' => 'En attente', 'running' => 'En cours', 'matched' => 'Rapproché', 'failed' => 'Échec'])->default('pending')->required(),
                    TextInput::make('scope')->label('Périmètre')->required()->maxLength(255),
                    DatePicker::make('period_start')->label('Période début'),
                    DatePicker::make('period_end')->label('Période fin'),
                    TextInput::make('discrepancies_count')->label('Écarts')->numeric()->default(0)->required(),
                    DateTimePicker::make('started_at')->label('Démarré le'),
                    DateTimePicker::make('completed_at')->label('Terminé le'),
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
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('paymentGateway.name')->label('Gateway'),
                TextColumn::make('discrepancies_count')->label('Écarts')->numeric(),
                TextColumn::make('completed_at')->label('Terminé')->dateTime(),
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
            'index' => ManageReconciliationLogs::route('/'),
        ];
    }
}

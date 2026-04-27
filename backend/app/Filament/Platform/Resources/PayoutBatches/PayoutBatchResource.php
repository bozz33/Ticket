<?php

namespace App\Filament\Platform\Resources\PayoutBatches;

use App\Filament\Platform\Resources\PayoutBatches\Pages\ManagePayoutBatches;
use App\Models\PaymentGateway;
use App\Models\PayoutBatch;
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

class PayoutBatchResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PayoutBatch::class;

    protected static ?string $permissionPrefix = 'platform.payout_batches';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Batch')->schema([
                    Select::make('payment_gateway_id')->label('Gateway')->options(fn (): array => PaymentGateway::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    TextInput::make('reference')->label('Référence')->required()->maxLength(255),
                    Select::make('status')->label('Statut')->options(['draft' => 'Brouillon', 'queued' => 'En file', 'processed' => 'Traité', 'failed' => 'Échec'])->default('draft')->required(),
                    TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3)->required(),
                    TextInput::make('tenant_count')->label('Nombre de tenants')->numeric()->default(0)->required(),
                    TextInput::make('gross_amount')->label('Brut')->numeric()->default(0)->required(),
                    TextInput::make('fee_amount')->label('Frais')->numeric()->default(0)->required(),
                    TextInput::make('net_amount')->label('Net')->numeric()->default(0)->required(),
                    DateTimePicker::make('scheduled_at')->label('Planifié le'),
                    DateTimePicker::make('processed_at')->label('Traité le'),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('paymentGateway.name')->label('Gateway'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('tenant_count')->label('Tenants')->numeric(),
                TextColumn::make('net_amount')->label('Net')->numeric(),
                TextColumn::make('processed_at')->label('Traité')->dateTime(),
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
            'index' => ManagePayoutBatches::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Platform\Resources\PlatformTransactions;

use App\Filament\Platform\Resources\PlatformTransactions\Pages\CreatePlatformTransaction;
use App\Filament\Platform\Resources\PlatformTransactions\Pages\EditPlatformTransaction;
use App\Filament\Platform\Resources\PlatformTransactions\Pages\ListPlatformTransactions;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\PlatformTransaction;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PlatformTransactionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformTransaction::class;

    protected static ?string $permissionPrefix = 'platform.transactions';

    protected static string|UnitEnum|null $navigationGroup = 'Finance & Comptabilité';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $recordTitleAttribute = 'transaction_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')->schema([
                    Select::make('tenant_id')->label('Tenant')->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->columnSpan(2),
                    Select::make('plan_id')->label('Plan')->options(fn (): array => Plan::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->columnSpan(2),
                    Select::make('payment_gateway_id')->label('Gateway')->options(fn (): array => PaymentGateway::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->columnSpan(2),
                    TextInput::make('transaction_reference')->label('Référence interne')->required()->maxLength(255)->columnSpan(3),
                    TextInput::make('gateway_reference')->label('Référence gateway')->maxLength(255)->columnSpan(3),
                    Select::make('type')->label('Type')->options([
                        'subscription' => 'Souscription',
                        'gateway_charge' => 'Encaissement gateway',
                        'refund' => 'Remboursement',
                        'settlement' => 'Reversement',
                        'adjustment' => 'Ajustement',
                    ])->required()->columnSpan(2),
                    Select::make('direction')->label('Sens')->options(['credit' => 'Crédit', 'debit' => 'Débit'])->default('credit')->required()->columnSpan(2),
                    Select::make('status')->label('Statut')->options([
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'success' => 'Succès',
                        'failed' => 'Échec',
                        'cancelled' => 'Annulé',
                    ])->default('pending')->required()->columnSpan(2),
                    TextInput::make('gross_amount')->label('Montant brut')->numeric()->default(0)->required()->columnSpan(2),
                    TextInput::make('fee_amount')->label('Frais')->numeric()->default(0)->required()->columnSpan(2),
                    TextInput::make('net_amount')->label('Montant net')->numeric()->default(0)->required()->columnSpan(1),
                    TextInput::make('currency_code')->label('Devise')->default('XOF')->maxLength(3)->required()->columnSpan(1),
                    DateTimePicker::make('occurred_at')->label('Date opération')->columnSpan(2),
                    KeyValue::make('meta')->label('Meta')->columnSpanFull(),
                ])->columns(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_reference')
            ->columns([
                TextColumn::make('transaction_reference')
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('tenant.name')->label('Tenant')->searchable(),
                TextColumn::make('plan.name')->label('Plan')->toggleable(),
                TextColumn::make('paymentGateway.name')->label('Gateway')->toggleable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('gross_amount')->label('Brut')->numeric(),
                TextColumn::make('fee_amount')->label('Frais')->numeric(),
                TextColumn::make('net_amount')->label('Net')->numeric(),
                TextColumn::make('currency_code')->label('Devise'),
                TextColumn::make('occurred_at')->label('Date')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'success' => 'Succès',
                        'failed' => 'Échec',
                        'cancelled' => 'Annulé',
                    ]),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'subscription' => 'Souscription',
                        'gateway_charge' => 'Encaissement gateway',
                        'refund' => 'Remboursement',
                        'settlement' => 'Reversement',
                        'adjustment' => 'Ajustement',
                    ]),
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('payment_gateway_id')
                    ->label('Gateway')
                    ->options(fn (): array => PaymentGateway::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
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
            'index' => ListPlatformTransactions::route('/'),
            'create' => CreatePlatformTransaction::route('/create'),
            'edit' => EditPlatformTransaction::route('/{record}/edit'),
        ];
    }
}

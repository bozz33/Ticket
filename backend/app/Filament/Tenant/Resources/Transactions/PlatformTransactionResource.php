<?php

namespace App\Filament\Tenant\Resources\Transactions;

use App\Filament\Tenant\Resources\Transactions\Pages\ListPlatformTransactions;
use App\Models\PlatformTransaction;
use App\Support\Filament\Concerns\HasPanelPermission;
use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PlatformTransactionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = PlatformTransaction::class;

    protected static ?string $permissionPrefix = 'tenant.finance';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions';

    protected static ?string $recordTitleAttribute = 'transaction_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_reference')->label('Référence')->searchable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('direction')->label('Sens')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('gross_amount')->label('Brut')->numeric(),
                TextColumn::make('fee_amount')->label('Commission')->numeric(),
                TextColumn::make('net_amount')->label('Net')->numeric(),
                TextColumn::make('currency_code')->label('Devise'),
                TextColumn::make('occurred_at')->label('Date')->dateTime(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformTransactions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = app(TenantContext::class)->get();

        return parent::getEloquentQuery()
            ->when(
                $tenant !== null,
                fn (Builder $query) => $query->where('tenant_id', $tenant->getKey()),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->latest('occurred_at');
    }
}

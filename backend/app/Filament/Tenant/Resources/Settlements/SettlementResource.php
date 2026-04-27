<?php

namespace App\Filament\Tenant\Resources\Settlements;

use App\Filament\Tenant\Resources\Settlements\Pages\ListSettlements;
use App\Models\Settlement;
use App\Support\Filament\Concerns\HasPanelPermission;
use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SettlementResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Settlement::class;

    protected static ?string $permissionPrefix = 'tenant.finance';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Demandes de reversement';

    protected static ?string $modelLabel = 'Demande de reversement';

    protected static ?string $pluralModelLabel = 'Demandes de reversement';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('gross_amount')->label('Brut')->numeric(),
                TextColumn::make('fee_amount')->label('Frais')->numeric(),
                TextColumn::make('net_amount')->label('Net')->numeric(),
                TextColumn::make('currency_code')->label('Devise'),
                TextColumn::make('period_end')->label('Période fin')->date(),
                TextColumn::make('scheduled_at')->label('Planifié le')->dateTime(),
                TextColumn::make('paid_at')->label('Payé le')->dateTime(),
            ])
            ->defaultSort('period_end', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettlements::route('/'),
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
            ->latest('period_end');
    }
}

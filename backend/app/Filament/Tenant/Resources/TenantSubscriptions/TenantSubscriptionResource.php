<?php

namespace App\Filament\Tenant\Resources\TenantSubscriptions;

use App\Filament\Tenant\Resources\TenantSubscriptions\Pages\ListTenantSubscriptions;
use App\Models\TenantSubscription;
use App\Support\Filament\Concerns\HasPanelPermission;
use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TenantSubscriptionResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = TenantSubscription::class;

    protected static ?string $permissionPrefix = 'tenant.finance';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Souscriptions';

    protected static ?string $modelLabel = 'Souscription';

    protected static ?string $pluralModelLabel = 'Souscriptions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')->label('Plan')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('started_at')->label('Début')->dateTime(),
                TextColumn::make('trial_ends_at')->label('Fin essai')->dateTime(),
                TextColumn::make('ends_at')->label('Expiration')->dateTime(),
                TextColumn::make('cancelled_at')->label('Annulée le')->dateTime(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenantSubscriptions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = app(TenantContext::class)->get();

        return parent::getEloquentQuery()
            ->with('plan')
            ->when(
                $tenant !== null,
                fn (Builder $query) => $query->where('tenant_id', $tenant->getKey()),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->latest('started_at');
    }
}

<?php

namespace App\Filament\Tenant\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Tenant\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Support\Filament\Concerns\HasPanelPermission;
use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Order::class;

    protected static ?string $permissionPrefix = 'tenant.sales';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'Commande';

    protected static ?string $pluralModelLabel = 'Commandes';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('offer.name')
                    ->label('Offre')
                    ->default('—'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('quantity')
                    ->label('Qté')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('total_amount')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state, Order $record): string => number_format($state / 100, 0, ',', ' ').' '.$record->currency_code)
                    ->alignEnd(),
                TextColumn::make('buyer_name')
                    ->label('Acheteur')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('buyer_email')
                    ->label('Email')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('access_passes_count')
                    ->label('Pass')
                    ->counts('accessPasses')
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(OrderStatus::options()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['offer'])->withCount('accessPasses');
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

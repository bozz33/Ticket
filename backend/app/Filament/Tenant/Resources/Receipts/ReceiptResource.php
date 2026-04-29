<?php

namespace App\Filament\Tenant\Resources\Receipts;

use App\Filament\Tenant\Resources\Receipts\Pages\ListReceipts;
use App\Models\Receipt;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ReceiptResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Receipt::class;

    protected static ?string $permissionPrefix = 'tenant.sales';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Reçus';

    protected static ?string $modelLabel = 'Reçu';

    protected static ?string $pluralModelLabel = 'Reçus';

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
                TextColumn::make('order.reference')
                    ->label('Commande')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'issued' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state, Receipt $record): string => number_format($state / 100, 0, ',', ' ').' '.$record->currency_code)
                    ->alignEnd(),
                TextColumn::make('buyer_name')
                    ->label('Acheteur')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('buyer_email')
                    ->label('Email')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('issued_at')
                    ->label('Émis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'issued' => 'Émis',
                        'cancelled' => 'Annulé',
                        'refunded' => 'Remboursé',
                    ]),
            ])
            ->defaultSort('issued_at', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceipts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

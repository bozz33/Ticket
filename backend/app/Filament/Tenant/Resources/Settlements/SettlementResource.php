<?php

namespace App\Filament\Tenant\Resources\Settlements;

use App\Filament\Tenant\Resources\Settlements\Pages\CreateSettlement;
use App\Filament\Tenant\Resources\Settlements\Pages\ListSettlements;
use App\Models\Settlement;
use App\Support\Filament\Concerns\HasPanelPermission;
use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Ticket\Payments\Contracts\PayoutManager;
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
        return $schema
            ->components([
                Section::make('Demande de reversement')->schema([
                    Placeholder::make('available_balance')
                        ->label('Solde disponible estimé')
                        ->content(function (): string {
                            $summary = static::availableBalanceSummary();

                            return number_format((int) ($summary['available_amount'] ?? 0), 0, ',', ' ')
                                .' '
                                .($summary['currency_code'] ?? 'XOF');
                        })
                        ->helperText(function (): string {
                            $summary = static::availableBalanceSummary();
                            $policy = (array) ($summary['policy'] ?? []);

                            return sprintf(
                                'Mature: %s | Reserve: %s | Deja reserve: %s | Delai: %s jour(s)',
                                number_format((int) ($summary['matured_amount'] ?? 0), 0, ',', ' '),
                                number_format((int) ($summary['reserve_hold_amount'] ?? 0), 0, ',', ' '),
                                number_format((int) ($summary['reserved_settlement_amount'] ?? 0), 0, ',', ' '),
                                number_format((int) ($policy['payout_delay_days'] ?? 0), 0, ',', ' '),
                            );
                        })
                        ->columnSpanFull(),
                    TextInput::make('gross_amount')
                        ->label('Montant demandé')
                        ->helperText(fn (): string => 'Montant minimum : '.number_format(static::minimumPayoutAmount(), 0, ',', ' ').' XOF. Les frais de reversement seront calculés automatiquement.')
                        ->numeric()
                        ->required()
                        ->minValue(static::minimumPayoutAmount())
                        ->maxValue(fn (): int => max(static::minimumPayoutAmount(), static::availableBalance())),
                    Placeholder::make('payout_preview')
                        ->label('Net estimé après frais')
                        ->content(function ($get): string {
                            $preview = static::previewPayout((int) ($get('gross_amount') ?? 0));
                            $currencyCode = (string) ($preview['currency_code'] ?? data_get(static::availableBalanceSummary(), 'currency_code', 'XOF'));

                            return sprintf(
                                '%s %s (frais %s %s)',
                                number_format((int) ($preview['net_amount'] ?? 0), 0, ',', ' '),
                                $currencyCode,
                                number_format((int) ($preview['fee_amount'] ?? 0), 0, ',', ' '),
                                $currencyCode,
                            );
                        }),
                    TextInput::make('currency_code')
                        ->label('Devise')
                        ->default('XOF')
                        ->maxLength(3)
                        ->required(),
                    TextInput::make('meta.request_note')
                        ->label('Note')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    KeyValue::make('meta.payout_details')
                        ->label('Coordonnées de paiement')
                        ->helperText('Exemples : banque, titulaire, IBAN/RIB, Mobile Money, numéro de réception.')
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('gross_amount')->label('Brut')->numeric(),
                TextColumn::make('payout_fee_amount')->label('Frais reversement')->numeric(),
                TextColumn::make('reserve_amount')->label('Reserve')->numeric()->toggleable(isToggledHiddenByDefault: true),
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
            'create' => CreateSettlement::route('/create'),
        ];
    }

    public static function availableBalance(): int
    {
        return (int) (static::availableBalanceSummary()['available_amount'] ?? 0);
    }

    public static function availableBalanceSummary(): array
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return [
                'available_amount' => 0,
                'matured_amount' => 0,
                'reserve_hold_amount' => 0,
                'reserved_settlement_amount' => 0,
                'currency_code' => 'XOF',
                'policy' => null,
            ];
        }

        return app(PayoutManager::class)->availableBalance($tenant, (string) ($tenant->currency_code ?: 'XOF'));
    }

    public static function minimumPayoutAmount(): int
    {
        return (int) data_get(static::availableBalanceSummary(), 'policy.minimum_payout_amount', 1000);
    }

    public static function previewPayout(int $grossAmount): array
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return [
                'gross_amount' => $grossAmount,
                'fee_amount' => 0,
                'net_amount' => $grossAmount,
                'currency_code' => 'XOF',
            ];
        }

        return app(PayoutManager::class)->computePayout($tenant, $grossAmount, (string) ($tenant->currency_code ?: 'XOF'));
    }

    public static function canCreate(): bool
    {
        return static::allows('update') && static::availableBalance() >= static::minimumPayoutAmount();
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

<?php

namespace App\Filament\Platform\Resources\Refunds;

use App\Enums\RefundStatus;
use App\Filament\Platform\Resources\Refunds\Pages\CreateRefund;
use App\Filament\Platform\Resources\Refunds\Pages\ListRefunds;
use App\Models\PlatformTransaction;
use App\Models\Refund;
use App\Models\Tenant;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Ticket\Payments\Contracts\RefundManager;
use Ticket\Payments\Domain\PaymentStatuses;
use UnitEnum;

class RefundResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Refund::class;

    protected static ?string $permissionPrefix = 'platform.refunds';

    protected static string|UnitEnum|null $navigationGroup = 'Finance plateforme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Remboursements';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Remboursement';

    protected static ?string $pluralModelLabel = 'Remboursements';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sélection de la transaction')->schema([
                    Select::make('platform_transaction_id')
                        ->label('Transaction à rembourser')
                        ->options(static::transactionOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Placeholder::make('refund_preview')
                        ->label('Prévisualisation')
                        ->content(function ($get): string {
                            $transactionId = (int) ($get('platform_transaction_id') ?? 0);
                            $reasonCode = (string) ($get('reason_code') ?? 'manual_refund');

                            if ($transactionId <= 0) {
                                return 'Choisissez une transaction pour voir le calcul du remboursement.';
                            }

                            $transaction = PlatformTransaction::query()->find($transactionId);

                            if (! $transaction) {
                                return 'Transaction introuvable.';
                            }

                            try {
                                $quote = app(RefundManager::class)->quote($transaction, $reasonCode);
                            } catch (\Throwable $exception) {
                                return $exception->getMessage();
                            }

                            return sprintf(
                                'Client: %s %s | Organisateur: %s %s | Plateforme absorbée: %s %s | Frais client remboursés: %s %s',
                                number_format((int) ($quote['customer_refund_amount'] ?? 0), 0, ',', ' '),
                                $quote['currency_code'] ?? 'XOF',
                                number_format((int) ($quote['organizer_reversal_amount'] ?? 0), 0, ',', ' '),
                                $quote['currency_code'] ?? 'XOF',
                                number_format((int) ($quote['platform_absorption_amount'] ?? 0), 0, ',', ' '),
                                $quote['currency_code'] ?? 'XOF',
                                number_format((int) ($quote['customer_fee_refunded'] ?? 0), 0, ',', ' '),
                                $quote['currency_code'] ?? 'XOF',
                            );
                        })
                        ->columnSpanFull(),
                    Select::make('reason_code')
                        ->label('Motif')
                        ->options([
                            'event_cancelled' => 'Événement annulé',
                            'event_rescheduled' => 'Report incompatible',
                            'duplicate_charge' => 'Débit en doublon',
                            'technical_issue' => 'Erreur technique confirmée',
                            'customer_request' => 'Demande client validée',
                            'fraud_prevention' => 'Prévention fraude',
                            'manual_refund' => 'Remboursement manuel',
                        ])
                        ->default('manual_refund')
                        ->required(),
                    Textarea::make('reason')
                        ->label('Commentaire')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->formatStateUsing(fn (?string $state): string => $state ?: '—')
                    ->searchable(),
                TextColumn::make('transaction.transaction_reference')
                    ->label('Transaction source')
                    ->searchable(),
                TextColumn::make('paymentGateway.name')
                    ->label('Gateway')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (RefundStatus $state): string => $state->color()),
                TextColumn::make('reason_code')
                    ->label('Motif')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('amount_refunded_to_buyer')
                    ->label('Montant client')
                    ->numeric(),
                TextColumn::make('organizer_reversal_amount')
                    ->label('Déduction organisateur')
                    ->numeric(),
                TextColumn::make('platform_absorption_amount')
                    ->label('Absorption plateforme')
                    ->numeric(),
                TextColumn::make('currency_code')
                    ->label('Devise'),
                TextColumn::make('processed_at')
                    ->label('Traité le')
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(RefundStatus::options()),
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('sync')
                    ->label('Synchroniser')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(function (Refund $record): bool {
                        $user = Filament::auth()->user();
                        $canUpdate = (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                            || (method_exists($user, 'can') && $user->can('platform.refunds.update'));

                        return $canUpdate
                            && in_array($record->status, [RefundStatus::Pending, RefundStatus::Processing], true);
                    })
                    ->action(function (Refund $record): void {
                        $refund = app(RefundManager::class)->sync($record);

                        Notification::make()
                            ->title('Remboursement synchronisé')
                            ->body(sprintf(
                                'Le remboursement %s est maintenant au statut %s.',
                                $refund->reference,
                                RefundStatus::options()[$refund->status->value] ?? $refund->status->value,
                            ))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRefunds::route('/'),
            'create' => CreateRefund::route('/create'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['tenant', 'transaction']);
    }

    private static function transactionOptions(): array
    {
        return PlatformTransaction::query()
            ->where('direction', 'credit')
            ->whereIn('status', PaymentStatuses::successful())
            ->whereDoesntHave('refunds', fn (Builder $query) => $query->whereIn('status', [
                RefundStatus::Pending->value,
                RefundStatus::Processing->value,
                RefundStatus::Refunded->value,
            ]))
            ->with('tenant')
            ->orderByDesc('occurred_at')
            ->get()
            ->mapWithKeys(fn (PlatformTransaction $transaction): array => [
                $transaction->getKey() => sprintf(
                    '%s — %s — %s %s',
                    $transaction->transaction_reference,
                    $transaction->tenant?->name ?: 'Tenant inconnu',
                    number_format((int) $transaction->gross_amount, 0, ',', ' '),
                    $transaction->currency_code,
                ),
            ])
            ->all();
    }
}

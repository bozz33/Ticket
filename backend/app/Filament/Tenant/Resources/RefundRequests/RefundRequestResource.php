<?php

namespace App\Filament\Tenant\Resources\RefundRequests;

use App\Enums\OrderStatus;
use App\Filament\Tenant\Resources\RefundRequests\Pages\ListRefundRequests;
use App\Models\Order;
use App\Models\User;
use App\Support\Filament\Concerns\HasPanelPermission;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Ticket\Ticketing\Application\BuyerRefundRequestService;
use UnitEnum;

class RefundRequestResource extends Resource
{
    use HasPanelPermission;

    protected static ?string $model = Order::class;

    protected static ?string $permissionPrefix = 'tenant.refund_requests';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Demandes de remboursement';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Demande de remboursement';

    protected static ?string $pluralModelLabel = 'Demandes de remboursement';

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?string $slug = 'refund-requests';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('reference')
                    ->label('Commande')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('buyer_name')
                    ->label('Acheteur')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('buyer_email')
                    ->label('E-mail')
                    ->default('—')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state, Order $record): string => number_format($state, 0, ',', ' ').' '.$record->currency_code)
                    ->alignEnd(),
                TextColumn::make('meta.refund_request.reason_code')
                    ->label('Motif')
                    ->formatStateUsing(fn (?string $state): string => static::reasonLabel($state)),
                TextColumn::make('meta.refund_request.status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::statusLabel($state))
                    ->color(fn (?string $state): string => static::statusColor($state)),
                TextColumn::make('meta.refund_request.requested_at')
                    ->label('Demandé le')
                    ->formatStateUsing(fn (?string $state): string => static::dateLabel($state))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('refund_status')
                    ->label('Statut')
                    ->options([
                        'pending_tenant_review' => 'À valider',
                        'approved' => 'Validée',
                        'rejected' => 'Rejetée',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('meta->refund_request->status', $data['value'])
                        : $query),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => static::isPending($record))
                    ->form([
                        Textarea::make('note')
                            ->label('Note pour la plateforme')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Valider cette demande ?')
                    ->modalDescription('La plateforme recevra ensuite une notification pour exécuter le remboursement financier.')
                    ->action(function (Order $record, array $data): void {
                        $actor = Filament::auth()->user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        app(BuyerRefundRequestService::class)->approve($record, $actor, $data);

                        Notification::make()
                            ->success()
                            ->title('Demande validée')
                            ->body('La plateforme a été notifiée pour traiter le remboursement.')
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => static::isPending($record))
                    ->form([
                        Textarea::make('reason')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter cette demande ?')
                    ->action(function (Order $record, array $data): void {
                        $actor = Filament::auth()->user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        app(BuyerRefundRequestService::class)->reject($record, $actor, $data);

                        Notification::make()
                            ->success()
                            ->title('Demande rejetée')
                            ->body('L’acheteur a été notifié.')
                            ->send();
                    }),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRefundRequests::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('meta->refund_request->requested_at')
            ->with(['buyer', 'receipt']);
    }

    public static function canCreate(): bool
    {
        return false;
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

    private static function isPending(Order $order): bool
    {
        return ((string) data_get($order->meta, 'refund_request.status', 'pending_tenant_review')) === 'pending_tenant_review'
            && ($order->status?->value ?? $order->status) === OrderStatus::RefundPending->value;
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status ?: 'pending_tenant_review') {
            'approved' => 'Validée',
            'rejected' => 'Rejetée',
            default => 'À valider',
        };
    }

    private static function statusColor(?string $status): string
    {
        return match ($status ?: 'pending_tenant_review') {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };
    }

    private static function reasonLabel(?string $reason): string
    {
        return match ($reason) {
            'event_cancelled' => 'Événement annulé',
            'event_rescheduled' => 'Report incompatible',
            'duplicate_charge' => 'Débit en doublon',
            'technical_issue' => 'Erreur technique',
            'customer_request' => 'Demande client',
            default => $reason ?: '—',
        };
    }

    private static function dateLabel(?string $value): string
    {
        if (! filled($value)) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}

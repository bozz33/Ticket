<?php

namespace Ticket\Ticketing\Application;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\PlatformUser;
use App\Models\Receipt;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\BuyerAccountActivityNotification;
use App\Notifications\BuyerRefundRequestPlatformNotification;
use App\Notifications\BuyerRefundRequestTenantNotification;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ticket\Notifications\Contracts\DomainEventPublisher;
use Ticket\Notifications\Contracts\NotificationDispatcher;
use Ticket\Payments\Contracts\RefundManager;
use Ticket\Ticketing\Contracts\OrderCatalog;

class BuyerRefundRequestService
{
    public function __construct(
        private readonly OrderCatalog $orderCatalog,
        private readonly RefundManager $refundManager,
        private readonly TenantContext $tenantContext,
        private readonly NotificationDispatcher $notifications,
        private readonly DomainEventPublisher $domainEvents,
    ) {}

    public function request(User $buyer, string $orderIdentifier, array $payload): Order
    {
        $tenant = $this->tenantContext->get();

        if (! $tenant instanceof Tenant) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant introuvable.',
            ]);
        }

        $order = $this->orderCatalog->findByIdentifierForBuyer($buyer, $orderIdentifier);

        if (! $order instanceof Order) {
            throw ValidationException::withMessages([
                'order' => 'Commande introuvable.',
            ]);
        }

        if (($order->status?->value ?? $order->status) !== OrderStatus::Confirmed->value) {
            throw ValidationException::withMessages([
                'order' => 'Seules les commandes confirmées peuvent faire l’objet d’une demande de remboursement.',
            ]);
        }

        if (data_get($order->meta, 'refund_request.requested_at')) {
            throw ValidationException::withMessages([
                'order' => 'Une demande de remboursement existe déjà pour cette commande.',
            ]);
        }

        $transaction = PlatformTransaction::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('transaction_reference', $order->transaction_reference)
            ->first();

        if (! $transaction instanceof PlatformTransaction) {
            throw ValidationException::withMessages([
                'order' => 'La transaction liée à cette commande est introuvable.',
            ]);
        }

        $reasonCode = trim((string) ($payload['reason_code'] ?? 'customer_request'));
        $reason = trim((string) ($payload['reason'] ?? ''));
        $quote = $this->refundManager->quote($transaction, $reasonCode);

        DB::connection(config('ticket.tenant_connection', 'tenant'))->transaction(function () use ($buyer, $order, $tenant, $reasonCode, $reason, $quote): void {
            $freshOrder = Order::query()
                ->with('receipt')
                ->findOrFail($order->getKey());

            $requestData = [
                'reason_code' => $reasonCode,
                'reason' => $reason !== '' ? $reason : null,
                'requested_at' => now()->toIso8601String(),
                'requested_by' => [
                    'id' => $buyer->getKey(),
                    'name' => $buyer->name,
                    'email' => $buyer->email,
                ],
                'quote' => $quote,
            ];

            $freshOrder->forceFill([
                'status' => OrderStatus::RefundPending,
                'meta' => array_merge((array) ($freshOrder->meta ?? []), [
                    'refund_request' => $requestData,
                ]),
            ])->save();

            if ($freshOrder->receipt instanceof Receipt) {
                $freshOrder->receipt->forceFill([
                    'meta' => array_merge((array) ($freshOrder->receipt->meta ?? []), [
                        'refund_request' => $requestData,
                    ]),
                ])->save();
            }

            $this->notifications->send(
                $buyer,
                new BuyerAccountActivityNotification(
                    'Demande de remboursement envoyée',
                    sprintf('Votre demande pour la commande %s est en cours de traitement.', $freshOrder->reference),
                    '/compte/remboursements',
                    'heroicon-o-arrow-uturn-left',
                ),
            );

            PlatformUser::query()
                ->where(function ($query): void {
                    $query->where('is_super_admin', true)
                        ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'super-admin'));
                })
                ->get()
                ->each(function (PlatformUser $platformUser) use ($tenant, $freshOrder, $requestData): void {
                    $this->notifications->send(
                        $platformUser,
                        new BuyerRefundRequestPlatformNotification($tenant, $freshOrder, $requestData),
                    );
                });

            User::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereHas('roles', fn ($roles) => $roles->where('name', 'owner'))
                        ->orWhereHas('permissions', fn ($permissions) => $permissions->where('name', 'tenant.access'));
                })
                ->get()
                ->each(function (User $tenantUser) use ($freshOrder, $requestData): void {
                    $this->notifications->send(
                        $tenantUser,
                        new BuyerRefundRequestTenantNotification($freshOrder, $requestData),
                    );
                });
        });

        $updatedOrder = $this->orderCatalog->findByIdentifierForBuyer($buyer, $orderIdentifier) ?? $order;

        $this->domainEvents->publish(
            'ticketing.refund.requested',
            [
                'tenant_id' => $tenant->getKey(),
                'tenant_public_id' => $tenant->public_id,
                'order_id' => $updatedOrder->getKey(),
                'order_reference' => $updatedOrder->reference,
                'buyer_id' => $buyer->getKey(),
                'reason_code' => $reasonCode,
            ],
            Order::class,
            (string) $updatedOrder->getKey(),
            ['module' => 'ticketing'],
        );

        return $updatedOrder;
    }
}

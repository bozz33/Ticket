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
use App\Notifications\BuyerRefundApprovedPlatformNotification;
use App\Notifications\BuyerRefundRequestTenantNotification;
use App\Support\Microservices\DomainEventBridge;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ticket\Notifications\Contracts\NotificationDispatcher;
use Ticket\Notifications\Domain\DomainEventNames;
use Ticket\Payments\Contracts\RefundManager;
use Ticket\Ticketing\Contracts\OrderCatalog;

class BuyerRefundRequestService
{
    private const STATUS_PENDING_TENANT_REVIEW = 'pending_tenant_review';

    private const STATUS_APPROVED = 'approved';

    private const STATUS_REJECTED = 'rejected';

    public function __construct(
        private readonly OrderCatalog $orderCatalog,
        private readonly RefundManager $refundManager,
        private readonly TenantContext $tenantContext,
        private readonly NotificationDispatcher $notifications,
        private readonly DomainEventBridge $domainEvents,
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

        DB::connection(config('ticket.tenant_connection', 'tenant'))->transaction(function () use ($buyer, $order, $reasonCode, $reason, $quote): void {
            $freshOrder = Order::query()
                ->with('receipt')
                ->findOrFail($order->getKey());

            $requestData = [
                'status' => self::STATUS_PENDING_TENANT_REVIEW,
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

            User::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereHas('roles', fn ($roles) => $roles->where('name', 'owner'))
                        ->orWhereHas('permissions', fn ($permissions) => $permissions->where('name', 'tenant.access'))
                        ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('name', 'tenant.access'));
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
            DomainEventNames::REFUND_REQUESTED,
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
            ['module' => 'ticketing', 'tenant_id' => (string) $tenant->getKey()],
        );

        return $updatedOrder;
    }

    public function approve(Order $order, User $actor, array $payload = []): Order
    {
        $tenant = $this->tenantContext->get();

        if (! $tenant instanceof Tenant) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant introuvable.',
            ]);
        }

        $note = trim((string) ($payload['note'] ?? ''));

        $freshOrder = DB::connection(config('ticket.tenant_connection', 'tenant'))->transaction(function () use ($order, $actor, $note): Order {
            $freshOrder = Order::query()
                ->with('receipt')
                ->findOrFail($order->getKey());

            $requestData = $this->reviewableRequestData($freshOrder);
            $requestData['status'] = self::STATUS_APPROVED;
            $requestData['approved_at'] = now()->toIso8601String();
            $requestData['approved_by'] = $this->actorPayload($actor);
            $requestData['organizer_note'] = $note !== '' ? $note : null;

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

            return $freshOrder->fresh(['receipt']) ?? $freshOrder;
        });

        if ($freshOrder->buyer instanceof User) {
            $this->notifications->send(
                $freshOrder->buyer,
                new BuyerAccountActivityNotification(
                    'Demande de remboursement validée',
                    sprintf('L’organisateur a validé votre demande pour la commande %s. La plateforme va traiter le remboursement.', $freshOrder->reference),
                    '/compte/remboursements',
                    'heroicon-o-check-circle',
                ),
            );
        }

        $transaction = PlatformTransaction::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('transaction_reference', $freshOrder->transaction_reference)
            ->first();

        PlatformUser::query()
            ->where(function ($query): void {
                $query->where('is_super_admin', true)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'super-admin'))
                    ->orWhereHas('permissions', fn ($permissions) => $permissions->whereIn('name', ['platform.refunds.create', 'platform.refunds.update']))
                    ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->whereIn('name', ['platform.refunds.create', 'platform.refunds.update']));
            })
            ->get()
            ->each(function (PlatformUser $platformUser) use ($tenant, $freshOrder, $transaction): void {
                $this->notifications->send(
                    $platformUser,
                    new BuyerRefundApprovedPlatformNotification($tenant, $freshOrder, $transaction),
                );
            });

        $this->domainEvents->publish(
            DomainEventNames::REFUND_APPROVED,
            [
                'tenant_id' => $tenant->getKey(),
                'tenant_public_id' => $tenant->public_id,
                'order_id' => $freshOrder->getKey(),
                'order_reference' => $freshOrder->reference,
                'buyer_id' => $freshOrder->buyer_user_id,
            ],
            Order::class,
            (string) $freshOrder->getKey(),
            ['module' => 'ticketing', 'tenant_id' => (string) $tenant->getKey()],
        );

        return $freshOrder;
    }

    public function reject(Order $order, User $actor, array $payload = []): Order
    {
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Le motif de rejet est obligatoire.',
            ]);
        }

        $freshOrder = DB::connection(config('ticket.tenant_connection', 'tenant'))->transaction(function () use ($order, $actor, $reason): Order {
            $freshOrder = Order::query()
                ->with(['buyer', 'receipt'])
                ->findOrFail($order->getKey());

            $requestData = $this->reviewableRequestData($freshOrder);
            $requestData['status'] = self::STATUS_REJECTED;
            $requestData['rejected_at'] = now()->toIso8601String();
            $requestData['rejected_by'] = $this->actorPayload($actor);
            $requestData['rejection_reason'] = $reason;

            $freshOrder->forceFill([
                'status' => OrderStatus::Confirmed,
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

            return $freshOrder->fresh(['buyer', 'receipt']) ?? $freshOrder;
        });

        if ($freshOrder->buyer instanceof User) {
            $this->notifications->send(
                $freshOrder->buyer,
                new BuyerAccountActivityNotification(
                    'Demande de remboursement rejetée',
                    sprintf('L’organisateur a rejeté votre demande pour la commande %s. Motif : %s', $freshOrder->reference, $reason),
                    '/compte/remboursements',
                    'heroicon-o-x-circle',
                ),
            );
        }

        $tenant = $this->tenantContext->get();

        $this->domainEvents->publish(
            DomainEventNames::ORDER_CANCELLED,
            [
                'tenant_id' => $tenant instanceof Tenant ? $tenant->getKey() : null,
                'tenant_public_id' => $tenant instanceof Tenant ? $tenant->public_id : null,
                'order_id' => $freshOrder->getKey(),
                'order_reference' => $freshOrder->reference,
                'buyer_id' => $freshOrder->buyer_user_id,
                'reason' => $reason,
            ],
            Order::class,
            (string) $freshOrder->getKey(),
            ['module' => 'ticketing', 'tenant_id' => $tenant instanceof Tenant ? (string) $tenant->getKey() : null],
        );

        return $freshOrder;
    }

    private function reviewableRequestData(Order $order): array
    {
        $requestData = (array) data_get($order->meta, 'refund_request', []);

        if (empty($requestData['requested_at'])) {
            throw ValidationException::withMessages([
                'refund_request' => 'Cette commande n’a pas de demande de remboursement.',
            ]);
        }

        $status = (string) ($requestData['status'] ?? self::STATUS_PENDING_TENANT_REVIEW);

        if ($status !== self::STATUS_PENDING_TENANT_REVIEW) {
            throw ValidationException::withMessages([
                'refund_request' => 'Cette demande a déjà été traitée par l’organisateur.',
            ]);
        }

        return $requestData;
    }

    private function actorPayload(User $actor): array
    {
        return [
            'id' => $actor->getKey(),
            'name' => $actor->name,
            'email' => $actor->email,
        ];
    }
}

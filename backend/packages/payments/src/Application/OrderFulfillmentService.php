<?php

namespace Ticket\Payments\Application;

use App\Enums\AccessPassType;
use App\Enums\OrderStatus;
use App\Models\AccessPass;
use App\Models\CrowdfundingCampaign;
use App\Models\CrowdfundingContribution;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Receipt;
use App\Notifications\BuyerOrderConfirmedNotification;
use App\Support\Microservices\DomainEventBridge;
use App\Support\References\ReferenceGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Notifications\Domain\DomainEventNames;

class OrderFulfillmentService
{
    private const EVENT_TICKET_MODEL = 'App\\Models\\EventTicket';

    public function __construct(
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly CheckoutItemResolver $checkoutItems,
        private readonly DomainEventBridge $events,
    ) {}

    /**
     * Fulfill a confirmed payment by creating an Order, Receipt and AccessPasses.
     * Idempotent: safe to call multiple times for the same transaction_reference.
     */
    public function fulfill(string $transactionReference, array $payload): ?Order
    {
        $metadata = (array) Arr::get($payload, 'data.metadata', []);
        $offerableType = (string) Arr::get($metadata, 'offerable_type', '');

        $isCrowdfunding = str_ends_with($offerableType, 'CrowdfundingCampaign');

        $offerId = (int) Arr::get($metadata, 'offer_id', 0);
        $eventTicketId = (int) Arr::get($metadata, 'event_ticket_id', 0);
        $orderableType = (string) Arr::get($metadata, 'orderable_type', '');
        $orderableId = (int) Arr::get($metadata, 'orderable_id', 0);
        $checkoutItemType = (string) Arr::get($metadata, 'checkout_item_type', '');
        $checkoutItemPublicId = (string) Arr::get($metadata, 'checkout_item_public_id', '');
        $checkoutItemTitle = (string) Arr::get($metadata, 'checkout_item_title', '');
        $quantity = max(1, (int) Arr::get($metadata, 'quantity', 1));
        $grossAmount = (int) Arr::get($payload, 'data.amount', 0);
        $currencyCode = strtoupper((string) Arr::get($payload, 'data.currency', 'XOF'));
        $buyerName = (string) Arr::get($metadata, 'buyer_name', '');
        $buyerEmail = (string) Arr::get($metadata, 'buyer_email', '');
        $buyerPhone = (string) Arr::get($metadata, 'buyer_phone', '');
        $contributorDisplayName = (string) Arr::get($metadata, 'contributor_display_name', '');
        $contributorIsAnonymous = (bool) Arr::get($metadata, 'contributor_is_anonymous', false);
        $buyerUserId = (int) Arr::get($metadata, 'buyer_user_id', 0) ?: null;
        $gatewayReference = (string) Arr::get($payload, 'data.reference', $transactionReference);
        $gatewayTransactionId = Arr::get($payload, 'data.id');
        $paymentMethod = (string) Arr::get($metadata, 'payment_method', '');
        $pricingSnapshot = (array) Arr::get($payload, 'data.pricing_snapshot', Arr::get($metadata, 'pricing_snapshot', []));

        $offer = $offerId > 0 ? Offer::query()->find($offerId) : null;
        $customUnitAmount = (int) Arr::get($metadata, 'custom_unit_amount', 0);
        $unitAmount = $customUnitAmount > 0
            ? $customUnitAmount
            : ($offer !== null ? $offer->price_amount : (int) ($grossAmount / max(1, $quantity)));

        if ($orderableType === '' && $orderableId <= 0 && $eventTicketId > 0) {
            $orderableType = self::EVENT_TICKET_MODEL;
            $orderableId = $eventTicketId;
        }

        if ($orderableType === '' && $orderableId <= 0 && $offer instanceof Offer) {
            $orderableType = Offer::class;
            $orderableId = (int) $offer->getKey();
        }

        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use (
            $metadata,
            $transactionReference,
            $offer,
            $offerId,
            $offerableType,
            $orderableType,
            $orderableId,
            $checkoutItemType,
            $checkoutItemPublicId,
            $checkoutItemTitle,
            $quantity,
            $unitAmount,
            $grossAmount,
            $currencyCode,
            $buyerName,
            $buyerEmail,
            $buyerPhone,
            $contributorDisplayName,
            $contributorIsAnonymous,
            $buyerUserId,
            $isCrowdfunding,
            $gatewayReference,
            $gatewayTransactionId,
            $paymentMethod,
            $pricingSnapshot,
        ): Order {
            /** @var Order $order */
            $order = Order::query()->firstOrCreate(
                ['transaction_reference' => $transactionReference],
                [
                    'reference' => $this->generateReference('ORD', $currencyCode),
                    'buyer_user_id' => $buyerUserId,
                    'offer_id' => $offerId > 0 ? $offerId : null,
                    'status' => OrderStatus::Confirmed,
                    'quantity' => $quantity,
                    'unit_amount' => $unitAmount,
                    'subtotal_amount' => (int) ($pricingSnapshot['subtotal'] ?? ($unitAmount * $quantity)),
                    'customer_fee_amount' => (int) ($pricingSnapshot['customer_fee_total'] ?? 0),
                    'total_amount' => $grossAmount,
                    'gateway_fee_amount_snapshot' => (int) ($pricingSnapshot['gateway_fee_amount'] ?? 0),
                    'platform_fee_amount_snapshot' => (int) ($pricingSnapshot['platform_fee_amount'] ?? 0),
                    'tax_amount_snapshot' => (int) ($pricingSnapshot['tax_amount'] ?? 0),
                    'organizer_net_amount_snapshot' => (int) ($pricingSnapshot['organizer_net'] ?? $grossAmount),
                    'currency_code' => $currencyCode,
                    'buyer_name' => $buyerName ?: null,
                    'buyer_email' => $buyerEmail ?: null,
                    'buyer_phone' => $buyerPhone ?: null,
                    'meta' => array_filter([
                        'transaction_reference' => $transactionReference,
                        'gateway_reference' => $gatewayReference,
                        'gateway_transaction_id' => $gatewayTransactionId,
                        'offerable_type' => $offerableType,
                        'checkout_item_type' => $checkoutItemType !== '' ? $checkoutItemType : null,
                        'checkout_item_public_id' => $checkoutItemPublicId !== '' ? $checkoutItemPublicId : null,
                        'checkout_item_title' => $checkoutItemTitle !== '' ? $checkoutItemTitle : null,
                        'orderable_type' => $orderableType !== '' ? $orderableType : null,
                        'orderable_id' => $orderableId > 0 ? $orderableId : null,
                        'event_ticket_id' => Arr::get($metadata, 'event_ticket_id'),
                        'event_ticket_public_id' => Arr::get($metadata, 'event_ticket_public_id'),
                        'event_ticket_title' => Arr::get($metadata, 'event_ticket_title'),
                        'event_ticket_category' => Arr::get($metadata, 'event_ticket_category'),
                        'event_ticket_category_code' => Arr::get($metadata, 'event_ticket_category_code'),
                        'contributor_display_name' => $contributorDisplayName !== '' ? $contributorDisplayName : null,
                        'contributor_is_anonymous' => $contributorIsAnonymous,
                        'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
                    ], fn ($value): bool => $value !== null && $value !== ''),
                    'pricing_snapshot' => $pricingSnapshot,
                ] + $this->orderableAttributes($orderableType, $orderableId),
            );

            if ($isCrowdfunding) {
                $this->recordCrowdfundingContribution(
                    $offer,
                    $order,
                    $grossAmount,
                    $quantity,
                    $contributorDisplayName,
                    $contributorIsAnonymous,
                    $order->wasRecentlyCreated
                );
            } else {
                $this->ensureReceipt($order);
                $accessPasses = $this->ensureAccessPasses($order, $offer, $offerableType, array_merge($metadata, [
                    'orderable_type' => $orderableType,
                    'orderable_id' => $orderableId,
                    'checkout_item_type' => $checkoutItemType,
                    'checkout_item_public_id' => $checkoutItemPublicId,
                    'checkout_item_title' => $checkoutItemTitle,
                ]));
            }

            $this->notifyBuyer($order);
            $this->publishOrderPaidEvent($order, $accessPasses ?? []);

            return $order->fresh(['receipt', 'accessPasses']);
        });
    }

    private function recordCrowdfundingContribution(
        ?Offer $offer,
        Order $order,
        int $grossAmount,
        int $quantity,
        string $contributorDisplayName,
        bool $contributorIsAnonymous,
        bool $isNewOrder
    ): void
    {
        if (! $isNewOrder || ! $offer instanceof Offer || $offer->offerable_type !== CrowdfundingCampaign::class) {
            return;
        }

        CrowdfundingContribution::query()->firstOrCreate(
            [
                'transaction_reference' => $order->transaction_reference,
                'order_id' => $order->getKey(),
            ],
            [
                'crowdfunding_campaign_id' => $offer->offerable_id,
                'offer_id' => $offer->getKey(),
                'buyer_user_id' => $order->buyer_user_id,
                'contributor_name' => $contributorDisplayName !== '' ? $contributorDisplayName : $order->buyer_name,
                'contributor_email' => $order->buyer_email,
                'contributor_phone' => $order->buyer_phone,
                'amount' => max(0, $order->subtotal_amount),
                'currency_code' => $order->currency_code,
                'status' => 'confirmed',
                'is_anonymous' => $contributorIsAnonymous,
                'paid_at' => now(),
                'meta' => [
                    'order_reference' => $order->reference,
                    'offer_name' => $offer->name,
                    'quantity' => $quantity,
                ],
            ],
        );

        CrowdfundingCampaign::query()
            ->whereKey($offer->offerable_id)
            ->increment('raised_amount', max(0, $order->subtotal_amount));

        $offer->increment('quantity_sold', max(1, $quantity));
    }

    private function ensureReceipt(Order $order): void
    {
        if ($order->receipt()->exists()) {
            return;
        }

        $orderMeta = (array) ($order->meta ?? []);

        Receipt::query()->create([
            'order_id' => $order->id,
            'buyer_user_id' => $order->buyer_user_id,
            'reference' => $this->generateReference('RCP', $order->currency_code),
            'status' => 'issued',
            'total_amount' => $order->total_amount,
            'currency_code' => $order->currency_code,
            'buyer_name' => $order->buyer_name,
            'buyer_email' => $order->buyer_email,
            'buyer_phone' => $order->buyer_phone,
            'issued_at' => now(),
            'meta' => [
                'order_reference' => $order->reference,
                'transaction_reference' => $order->transaction_reference,
                'gateway_reference' => $orderMeta['gateway_reference'] ?? $order->transaction_reference,
                'gateway_transaction_id' => $orderMeta['gateway_transaction_id'] ?? null,
                'payment_method' => $orderMeta['payment_method'] ?? null,
                'payment_method_label' => $this->paymentMethodLabel((string) ($orderMeta['payment_method'] ?? '')),
                'pricing_snapshot' => $order->pricing_snapshot,
            ],
        ]);
    }

    private function notifyBuyer(Order $order): void
    {
        if (! $order->wasRecentlyCreated || ! $order->buyer) {
            return;
        }

        $order->buyer->notify(new BuyerOrderConfirmedNotification($order));
    }

    private function publishOrderPaidEvent(Order $order, array $accessPasses): void
    {
        if (! $order->wasRecentlyCreated) {
            return;
        }

        $payload = [
            'order_id' => $order->getKey(),
            'order_reference' => $order->reference,
            'transaction_reference' => $order->transaction_reference,
            'buyer_email' => $order->buyer_email,
            'buyer_name' => $order->buyer_name,
            'total_amount' => $order->total_amount,
            'currency_code' => $order->currency_code,
            'access_passes_count' => count($accessPasses),
            'access_passes' => array_map(fn (AccessPass $pass): array => [
                'id' => $pass->getKey(),
                'access_code' => $pass->access_code,
                'holder_email' => $pass->holder_email,
                'holder_name' => $pass->holder_name,
                'type' => $pass->type,
                'status' => $pass->status,
                'meta' => $pass->meta ?? [],
            ], $accessPasses),
            'channels' => ['email'],
            'template_key' => DomainEventNames::ORDER_PAID,
        ];

        $metadata = [
            'module' => 'payments',
            'tenant_id' => tenant('id'),
            'channels' => ['email'],
            'template_key' => DomainEventNames::ORDER_PAID,
        ];

        $this->events->publish(DomainEventNames::ORDER_PAID, $payload, 'orders', (string) $order->getKey(), $metadata);

        foreach ($accessPasses as $accessPass) {
            $this->events->publish(DomainEventNames::ACCESS_PASS_ISSUED, [
                'order_id' => $order->getKey(),
                'order_reference' => $order->reference,
                'access_pass_id' => $accessPass->getKey(),
                'access_code' => $accessPass->access_code,
                'holder_email' => $accessPass->holder_email,
                'holder_name' => $accessPass->holder_name,
                'type' => $accessPass->type,
                'status' => $accessPass->status,
                'meta' => $accessPass->meta ?? [],
                'channels' => ['email'],
                'template_key' => DomainEventNames::ACCESS_PASS_ISSUED,
            ], 'access_passes', (string) $accessPass->getKey(), [
                'module' => 'ticketing',
                'tenant_id' => tenant('id'),
                'channels' => ['email'],
                'template_key' => DomainEventNames::ACCESS_PASS_ISSUED,
            ]);
        }
    }

    private function ensureAccessPasses(Order $order, ?Offer $offer, string $offerableType, array $checkout): array
    {
        $existingCount = $order->accessPasses()->count();

        if ($existingCount >= $order->quantity) {
            return [];
        }

        $passType = $offer !== null
            ? AccessPassType::fromOfferableType($offerableType)
            : AccessPassType::PurchasePass;

        $needed = $order->quantity - $existingCount;
        [$passableType, $passableId] = $this->passableTarget($offer, $checkout);

        $createdPasses = [];

        for ($i = 0; $i < $needed; $i++) {
            $createdPasses[] = AccessPass::query()->create([
                'access_code' => $this->generateAccessCode($order, $i + $existingCount),
                'order_id' => $order->id,
                'offer_id' => $offer?->id,
                'holder_user_id' => $order->buyer_user_id,
                'type' => $passType,
                'status' => 'active',
                'holder_name' => $order->buyer_name,
                'holder_email' => $order->buyer_email,
                'meta' => [
                    'order_reference' => $order->reference,
                    'seat_index' => $i + $existingCount + 1,
                    'checkout_item_type' => Arr::get($checkout, 'checkout_item_type'),
                    'checkout_item_public_id' => Arr::get($checkout, 'checkout_item_public_id'),
                    'checkout_item_title' => Arr::get($checkout, 'checkout_item_title'),
                    'passable_type' => $passableType,
                    'passable_id' => $passableId,
                    'event_ticket_id' => Arr::get($checkout, 'event_ticket_id'),
                    'event_ticket_public_id' => Arr::get($checkout, 'event_ticket_public_id'),
                    'event_ticket_title' => Arr::get($checkout, 'event_ticket_title'),
                    'event_ticket_category' => Arr::get($checkout, 'event_ticket_category'),
                    'event_ticket_category_code' => Arr::get($checkout, 'event_ticket_category_code'),
                ],
            ] + $this->passableAttributes($passableType, $passableId));
        }

        if (! $this->checkoutItems->confirm(array_merge($checkout, ['order_id' => $order->getKey()]), $needed) && $offer !== null) {
            $offer->increment('quantity_sold', $needed);
        }

        return $createdPasses;
    }

    private function generateReference(string $prefix, ?string $currencyCode = null): string
    {
        return $this->referenceGenerator->generate($prefix, $currencyCode);
    }

    private function generateAccessCode(Order $order, int $index): string
    {
        return hash('sha256', sprintf(
            '%s:%d:%d:%s',
            $order->transaction_reference,
            $order->id,
            $index,
            config('app.key'),
        ));
    }

    private function paymentMethodLabel(string $paymentMethod): ?string
    {
        $paymentMethod = strtolower(trim($paymentMethod));

        return match ($paymentMethod) {
            '' => null,
            'card' => 'Carte bancaire',
            'orange_money' => 'Orange Money',
            'mtn_money' => 'MTN Money',
            'moov_money' => 'Moov Money',
            'wave' => 'Wave',
            default => ucfirst(str_replace('_', ' ', $paymentMethod)),
        };
    }

    private function passableTarget(?Offer $offer, array $checkout): array
    {
        $type = (string) Arr::get($checkout, 'orderable_type', '');
        $id = (int) Arr::get($checkout, 'orderable_id', 0);

        if ($type !== '' && $id > 0) {
            return [$type, $id];
        }

        $eventTicketId = (int) Arr::get($checkout, 'event_ticket_id', 0);

        if ($eventTicketId > 0) {
            return [self::EVENT_TICKET_MODEL, $eventTicketId];
        }

        if ($offer instanceof Offer) {
            return [Offer::class, (int) $offer->getKey()];
        }

        return [null, null];
    }

    private function orderableAttributes(?string $type, ?int $id): array
    {
        if (! $this->tenantColumnExists('orders', 'orderable_type') || ! $this->tenantColumnExists('orders', 'orderable_id')) {
            return [];
        }

        if (! $type || ! $id) {
            return [];
        }

        return [
            'orderable_type' => $type,
            'orderable_id' => $id,
        ];
    }

    private function passableAttributes(?string $type, ?int $id): array
    {
        if (! $this->tenantColumnExists('access_passes', 'passable_type') || ! $this->tenantColumnExists('access_passes', 'passable_id')) {
            return [];
        }

        if (! $type || ! $id) {
            return [];
        }

        return [
            'passable_type' => $type,
            'passable_id' => $id,
        ];
    }

    private function tenantColumnExists(string $table, string $column): bool
    {
        static $columns = [];

        $connection = (string) config('ticket.tenant_connection', 'tenant');
        $key = $connection.'.'.$table.'.'.$column;

        if (array_key_exists($key, $columns)) {
            return $columns[$key];
        }

        try {
            return $columns[$key] = Schema::connection($connection)->hasColumn($table, $column);
        } catch (\Throwable) {
            return $columns[$key] = false;
        }
    }
}

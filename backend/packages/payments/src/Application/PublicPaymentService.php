<?php

namespace Ticket\Payments\Application;

use App\Enums\CommercialModule;
use App\Enums\OrderStatus;
use App\Models\CrowdfundingCampaign;
use App\Models\Offer;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PlatformTransaction;
use App\Models\Tenant;
use App\Services\PlatformSettingsService;
use App\Support\Payments\GatewayAmountConverter;
use App\Support\References\ReferenceGenerator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;
use Ticket\Payments\Domain\PaidAmountGuard;
use Ticket\Payments\Domain\PaymentStatuses;
use Ticket\Ticketing\Contracts\OrderCatalog;

class PublicPaymentService
{
    public function __construct(
        private readonly FinancePolicyCatalog $financePolicyService,
        private readonly PlatformSettingsService $platformSettingsService,
        private readonly OrderFulfillmentService $orderFulfillmentService,
        private readonly OrderCatalog $orderCatalog,
        private readonly PaymentGatewayCredentialResolver $credentialResolver,
        private readonly PricingRuleEngine $pricingRuleEngine,
        private readonly PaymentGatewayHttpClientFactory $httpClientFactory,
        private readonly GatewayAmountConverter $amountConverter,
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly CheckoutItemResolver $checkoutItems,
    ) {}

    public function options(
        Tenant $tenant,
        string $offerIdentifier,
        int $requestedQuantity = 1,
        ?string $paymentMethod = null,
        ?string $checkoutItemType = null,
        ?int $customAmount = null,
    ): array {
        $item = $this->resolveCheckoutItem($offerIdentifier, $checkoutItemType);
        $offer = $item->pricingOffer;
        $isCrowdfunding = $this->isCrowdfundingItem($item);
        $customContributionAmount = $isCrowdfunding ? $this->customContributionAmount($customAmount) : null;
        $bounds = $this->checkoutItems->quantityBounds($item);
        if ($customContributionAmount !== null) {
            $bounds = ['min' => 1, 'max' => 1, 'max_per_account' => null];
        }
        $quantity = $customContributionAmount !== null ? 1 : min(max($requestedQuantity, $bounds['min']), $bounds['max']);
        $unitAmount = $customContributionAmount ?? $item->unitAmount;
        $currencyCode = (string) ($item->currencyCode ?: $tenant->currency_code ?: 'XOF');

        if ($isCrowdfunding && $unitAmount <= 0) {
            throw new RuntimeException('Montant de contribution requis.');
        }

        $gateway = $unitAmount > 0
            ? $this->pricingRuleEngine->resolveGatewayForCurrency($currencyCode, $paymentMethod)
            : null;
        $pricing = $customContributionAmount !== null
            ? $this->pricingRuleEngine->quoteCustomAmount($tenant, $customContributionAmount, $currencyCode, CommercialModule::Crowdfunding, $paymentMethod)
            : $this->pricingRuleEngine->quoteCheckoutItem($tenant, $item, $quantity, $gateway, $paymentMethod);

        return [
            'methods' => $this->buildPaymentMethods($gateway, $pricing['total'] <= 0),
            'pricing' => $pricing,
            'quantity' => $bounds,
            'proforma_reference' => $this->generateReference('ORD', (string) $item->currencyCode),
            'offer' => [
                'id' => $offer?->public_id ?? $item->publicId,
                'title' => $offer?->name ?? $item->title,
                'currency' => $offer?->currency_code ?? $item->currencyCode,
                'unit_amount' => $unitAmount,
            ],
            'checkout_item' => [
                'type' => $item->type,
                'id' => $item->publicId,
                'title' => $item->title,
            ],
            'ticket' => $item->is('event_ticket') ? [
                'id' => $item->publicId,
                'title' => $item->title,
                'availability' => data_get($item->metadata, 'availability'),
            ] : null,
            'tenant' => $tenant->only(['public_id', 'name', 'slug']),
        ];
    }

    public function initialize(Tenant $tenant, array $payload): array
    {
        $item = $this->resolveCheckoutItemFromPayload($payload);
        $offer = $item->pricingOffer;
        $isCrowdfunding = $this->isCrowdfundingItem($item);
        $customContributionAmount = $isCrowdfunding
            ? $this->customContributionAmount($payload['custom_amount'] ?? null)
            : null;
        $bounds = $this->checkoutItems->quantityBounds($item);
        if ($customContributionAmount !== null) {
            $bounds = ['min' => 1, 'max' => 1, 'max_per_account' => null];
        }
        $quantity = $customContributionAmount !== null ? 1 : (int) ($payload['quantity'] ?? 1);

        if ($quantity < $bounds['min'] || $quantity > $bounds['max']) {
            throw new RuntimeException('Quantité invalide pour cette offre.');
        }

        if ($isCrowdfunding && ($customContributionAmount ?? $item->unitAmount) <= 0) {
            throw new RuntimeException('Montant de contribution requis.');
        }

        $buyerEmail = Str::lower(trim((string) ($payload['buyer_email'] ?? '')));
        $buyerUserId = (int) ($payload['buyer_user_id'] ?? 0) ?: null;

        if ($buyerEmail === '') {
            throw new RuntimeException('Adresse email requise pour le paiement.');
        }

        $this->assertBuyerCanPurchase($tenant, $item, $buyerEmail, $buyerUserId, $quantity);

        $paymentMethod = trim((string) ($payload['payment_method'] ?? ''));
        $unitAmount = $customContributionAmount ?? $item->unitAmount;
        $currencyCode = (string) ($item->currencyCode ?: $tenant->currency_code ?: 'XOF');
        $gateway = $unitAmount > 0
            ? $this->pricingRuleEngine->resolveGatewayForCurrency($currencyCode, $paymentMethod)
            : null;
        $pricing = $customContributionAmount !== null
            ? $this->pricingRuleEngine->quoteCustomAmount($tenant, $customContributionAmount, $currencyCode, CommercialModule::Crowdfunding, $paymentMethod)
            : $this->pricingRuleEngine->quoteCheckoutItem($tenant, $item, $quantity, $gateway, $paymentMethod);
        $reference = $this->generateReference($pricing['total'] <= 0 ? 'FREE' : 'PAY', (string) $pricing['currency']);
        $module = $offer instanceof Offer
            ? $this->moduleFromOfferableType((string) $offer->offerable_type)
            : ($item->is('event_ticket') ? CommercialModule::Ticketing->value : CommercialModule::Ticketing->value);
        $reservation = null;
        $transaction = null;

        $metadata = array_merge([
            'tenant_slug' => $tenant->slug,
            'tenant_public_id' => $tenant->public_id,
            'offer_id' => $offer?->id,
            'offer_public_id' => $offer?->public_id,
            'offerable_type' => $offer?->offerable_type,
            'offer_title' => $offer?->name,
            'checkout_item_type' => $item->type,
            'checkout_item_public_id' => $item->publicId,
            'checkout_item_title' => $item->title,
            'orderable_type' => $item->orderableType,
            'orderable_id' => $item->orderableId,
            'custom_amount' => $customContributionAmount,
            'custom_unit_amount' => $customContributionAmount,
            'quantity' => $quantity,
            'buyer_user_id' => $buyerUserId,
            'buyer_name' => trim((string) ($payload['buyer_name'] ?? '')),
            'buyer_email' => $buyerEmail,
            'buyer_phone' => trim((string) ($payload['buyer_phone'] ?? '')),
            'contributor_display_name' => trim((string) ($payload['contributor_display_name'] ?? '')),
            'contributor_is_anonymous' => (bool) ($payload['contributor_is_anonymous'] ?? false),
            'module' => $module,
            'content_module' => trim((string) ($payload['content_module'] ?? '')),
            'content_slug' => trim((string) ($payload['content_slug'] ?? '')),
            'callback_url' => trim((string) ($payload['callback_url'] ?? '')),
            'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
            'pricing_snapshot' => $pricing,
        ], $item->metadata);

        try {
            $reservation = $this->checkoutItems->reserve($item, $quantity, [
                'transaction_reference' => $reference,
                'buyer_user_id' => $buyerUserId,
                'buyer_email' => $buyerEmail,
                'ticket_reservation_public_id' => trim((string) ($payload['ticket_reservation'] ?? '')),
            ]);

            if ($reservation instanceof CheckoutReservation) {
                $metadata = array_merge($metadata, [
                    'reservation_type' => $reservation->type,
                    'reservation_public_id' => $reservation->publicId,
                    'reservation_expires_at' => $reservation->expiresAt?->toIso8601String(),
                ], $reservation->metadata);
            }

            $transaction = PlatformTransaction::query()->create([
                'tenant_id' => $tenant->id,
                'payment_gateway_id' => $gateway?->id,
                'transaction_reference' => $reference,
                'gateway_reference' => null,
                'type' => 'public_checkout',
                'direction' => 'credit',
                'status' => 'pending',
                'gross_amount' => $pricing['total'],
                'fee_amount' => $pricing['total_fee_amount'],
                'net_amount' => $pricing['organizer_net'],
                'gateway_fee_amount' => $pricing['gateway_fee_amount'],
                'platform_fee_amount' => $pricing['platform_fee_amount'],
                'tax_amount' => $pricing['tax_amount'],
                'payout_fee_amount' => 0,
                'customer_fee_amount' => $pricing['customer_fee_total'],
                'absorbed_fee_amount' => $pricing['absorbed_fee_total'],
                'currency_code' => $pricing['currency'],
                'occurred_at' => now(),
                'meta' => [
                    'pricing' => $pricing,
                    'checkout' => $metadata,
                ],
                'pricing_snapshot' => $pricing,
            ]);

            Log::channel((string) config('ticket.logging.payments_channel', 'payments'))->info('public_checkout_initialized', [
                'tenant_id' => $tenant->getKey(),
                'transaction_reference' => $reference,
                'offer_public_id' => $offer?->public_id,
                'offer_title' => $offer?->name ?? $item->title,
                'checkout_item_type' => $item->type,
                'checkout_item_public_id' => $item->publicId,
                'quantity' => $quantity,
                'payment_method' => $paymentMethod !== '' ? $paymentMethod : 'auto',
                'gross_amount' => $pricing['total'],
                'currency' => $pricing['currency'],
                'buyer_user_id' => $buyerUserId,
            ]);

            if ($pricing['total'] <= 0) {
                $paymentPayload = $this->buildInternalSuccessPayload($reference, $pricing, $metadata);
                $transaction->forceFill([
                    'status' => 'success',
                    'meta' => array_merge((array) $transaction->meta, ['gateway_payload' => $paymentPayload]),
                ])->save();

                $order = $this->fulfillInTenant($tenant, $reference, $paymentPayload);

                Log::channel((string) config('ticket.logging.payments_channel', 'payments'))->info('public_checkout_fulfilled_free', [
                    'tenant_id' => $tenant->getKey(),
                    'transaction_reference' => $reference,
                    'order_reference' => $order?->reference,
                    'offer_public_id' => $offer?->public_id,
                    'checkout_item_type' => $item->type,
                    'checkout_item_public_id' => $item->publicId,
                    'quantity' => $quantity,
                ]);

                return [
                    'mode' => 'free',
                    'reference' => $reference,
                    'status' => 'confirmed',
                    'authorization_url' => null,
                    'order' => $order?->only(['public_id', 'reference']),
                ];
            }

            if (! $gateway) {
                throw new RuntimeException('Aucune passerelle de paiement active n’est disponible pour cette devise.');
            }

            return match ($gateway->code) {
                'paystack' => $this->initializePaystack($transaction, $gateway, $item, $pricing, $metadata),
                default => throw new RuntimeException('Gateway de paiement non prise en charge.'),
            };
        } catch (\Throwable $exception) {
            if ($reservation instanceof CheckoutReservation) {
                $this->checkoutItems->release($metadata);
            }

            if ($transaction instanceof PlatformTransaction) {
                $meta = (array) ($transaction->meta ?? []);
                data_set($meta, 'checkout.reservation_released_at', now()->toIso8601String());
                $transaction->forceFill([
                    'status' => 'failed',
                    'meta' => $meta,
                ])->save();
            }

            throw $exception;
        }
    }

    public function verify(Tenant $tenant, string $reference): array
    {
        $transaction = PlatformTransaction::query()
            ->with('paymentGateway')
            ->where('tenant_id', $tenant->id)
            ->where('transaction_reference', $reference)
            ->first();

        if (! $transaction) {
            throw new RuntimeException('Transaction introuvable.');
        }

        $order = $this->findOrderInTenant($tenant, $reference);

        if (PaymentStatuses::isSuccessful((string) $transaction->status)) {
            if ($order === null) {
                $pricingSnapshot = (array) ($transaction->pricing_snapshot ?? []);
                $payload = (array) data_get($transaction->meta ?? [], 'gateway_payload', $this->buildInternalSuccessPayload(
                    $reference,
                    [
                        'subtotal' => (int) data_get($pricingSnapshot, 'subtotal', 0),
                        'service_fee' => (int) data_get($pricingSnapshot, 'customer_fee_total', $transaction->customer_fee_amount),
                        'total' => (int) $transaction->gross_amount,
                        'currency' => (string) $transaction->currency_code,
                        'quantity' => (int) data_get($transaction->meta ?? [], 'checkout.quantity', 1),
                        'gateway_fee_amount' => (int) $transaction->gateway_fee_amount,
                        'platform_fee_amount' => (int) $transaction->platform_fee_amount,
                        'tax_amount' => (int) $transaction->tax_amount,
                        'organizer_net' => (int) $transaction->net_amount,
                        'customer_fee_total' => (int) $transaction->customer_fee_amount,
                        'total_fee_amount' => (int) $transaction->fee_amount,
                        'breakdown' => (array) data_get($pricingSnapshot, 'breakdown', []),
                    ],
                    (array) data_get($transaction->meta ?? [], 'checkout', []),
                ));
                $order = $this->fulfillInTenant($tenant, $reference, $payload);
            }

            return $this->mapVerification($transaction->fresh('paymentGateway'), $order);
        }

        $gateway = $transaction->paymentGateway;

        if (! $gateway) {
            throw new RuntimeException('Aucune gateway associée à cette transaction.');
        }

        return match ($gateway->code) {
            'paystack' => $this->verifyPaystack($tenant, $transaction, $gateway),
            default => throw new RuntimeException('Gateway de vérification non prise en charge.'),
        };
    }

    private function initializePaystack(
        PlatformTransaction $transaction,
        PaymentGateway $gateway,
        CheckoutItem $item,
        array $pricing,
        array $metadata,
    ): array {
        $secretKey = $this->credentialResolver->secretKey($gateway);
        if (blank($secretKey)) {
            throw new RuntimeException('La configuration Paystack est incomplète.');
        }

        $this->credentialResolver->assertPaystackKeyMatchesMode($gateway, $secretKey);

        $callbackUrl = (string) ($metadata['callback_url'] ?? '');

        if ($callbackUrl === '') {
            throw new RuntimeException('URL de retour paiement manquante.');
        }

        $gatewayAmount = $this->amountConverter->toGateway(
            (int) $pricing['total'],
            (string) $pricing['currency'],
            $gateway->code,
        );

        try {
            $response = $this->httpClientFactory
                ->forGateway($gateway)
                ->withToken($secretKey)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $metadata['buyer_email'],
                    'amount' => $gatewayAmount,
                    'currency' => $pricing['currency'],
                    'reference' => $transaction->transaction_reference,
                    'callback_url' => $callbackUrl,
                    'metadata' => $metadata,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Connexion à Paystack impossible. Vérifiez la connectivité sortante ou la configuration proxy.', previous: $exception);
        }

        $payload = $response->json();

        if (! $response->successful() || ! data_get($payload, 'status')) {
            throw new RuntimeException((string) (data_get($payload, 'message') ?? 'Initialisation du paiement impossible.'));
        }

        $transaction->forceFill([
            'payment_gateway_id' => $gateway->id,
            'gateway_reference' => (string) (data_get($payload, 'data.reference') ?? $transaction->transaction_reference),
            'status' => 'pending',
            'meta' => array_merge((array) $transaction->meta, [
                'checkout' => $metadata,
                'offer' => [
                    'public_id' => $item->pricingOffer?->public_id,
                    'name' => $item->pricingOffer?->name ?? $item->title,
                ],
                'gateway_amount' => [
                    'subunit_amount' => $gatewayAmount,
                    'display_amount' => (int) $pricing['total'],
                ],
                'gateway_initialize' => $payload,
                'gateway_code' => $gateway->code,
            ]),
        ])->save();

        return [
            'mode' => 'redirect',
            'reference' => $transaction->transaction_reference,
            'status' => 'pending',
            'access_code' => (string) data_get($payload, 'data.access_code'),
            'public_key' => null,
            'callback_url' => $callbackUrl,
            'authorization_url' => (string) data_get($payload, 'data.authorization_url'),
        ];
    }

    private function verifyPaystack(Tenant $tenant, PlatformTransaction $transaction, PaymentGateway $gateway): array
    {
        $secretKey = $this->credentialResolver->secretKey($gateway);

        if (blank($secretKey)) {
            throw new RuntimeException('La configuration Paystack est incomplète.');
        }

        $this->credentialResolver->assertPaystackKeyMatchesMode($gateway, $secretKey);

        try {
            $response = $this->httpClientFactory
                ->forGateway($gateway)
                ->withToken($secretKey)
                ->get(sprintf('https://api.paystack.co/transaction/verify/%s', $transaction->transaction_reference));
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Connexion à Paystack impossible. Vérifiez la connectivité sortante ou la configuration proxy.', previous: $exception);
        }

        $payload = $response->json();

        if (! $response->successful() || ! data_get($payload, 'status')) {
            throw new RuntimeException((string) (data_get($payload, 'message') ?? 'Vérification du paiement impossible.'));
        }

        $status = (string) data_get($payload, 'data.status', 'pending');
        $paidAt = data_get($payload, 'data.paid_at');
        $currency = strtoupper((string) data_get($payload, 'data.currency', $transaction->currency_code ?? 'XOF'));
        $amount = $this->amountConverter->fromGateway(
            (int) data_get($payload, 'data.amount', $transaction->gross_amount),
            $currency,
            $gateway->code,
        );

        if (PaymentStatuses::isSuccessful($status)) {
            // Same integrity guard as the webhook path: a successful charge must cover the
            // amount the server quoted at checkout before it can be fulfilled into an order.
            PaidAmountGuard::assertNotUnderpaid(
                (int) data_get($transaction->pricing_snapshot ?? [], 'total', $transaction->gross_amount),
                (string) ($transaction->currency_code ?: $currency),
                $amount,
                $currency,
                (string) $transaction->transaction_reference,
            );
        }

        $gatewayFees = $this->amountConverter->fromGateway(
            (int) data_get($payload, 'data.fees', 0),
            $currency,
            $gateway->code,
        );
        $pricingSnapshot = $this->financePolicyService->applyGatewayFeeToSnapshot(
            (array) ($transaction->pricing_snapshot ?? data_get($transaction->meta ?? [], 'pricing', [])),
            $gatewayFees,
        );
        $platformFees = (int) ($pricingSnapshot['platform_fee_amount'] ?? $transaction->platform_fee_amount ?? 0);
        $taxAmount = (int) ($pricingSnapshot['tax_amount'] ?? $transaction->tax_amount ?? 0);
        $customerFeeAmount = (int) ($pricingSnapshot['customer_fee_total'] ?? $transaction->customer_fee_amount ?? 0);
        $absorbedFeeAmount = (int) ($pricingSnapshot['absorbed_fee_total'] ?? $transaction->absorbed_fee_amount ?? 0);
        $organizerNet = (int) ($pricingSnapshot['organizer_net'] ?? $transaction->net_amount ?? 0);
        $fees = (int) ($pricingSnapshot['total_fee_amount'] ?? ($platformFees + $customerFeeAmount + $taxAmount));

        $transaction->forceFill([
            'payment_gateway_id' => $gateway->id,
            'gateway_reference' => (string) (data_get($payload, 'data.reference') ?? $transaction->gateway_reference ?? $transaction->transaction_reference),
            'status' => $status,
            'gross_amount' => $amount,
            'fee_amount' => $fees,
            'net_amount' => $organizerNet,
            'gateway_fee_amount' => $gatewayFees,
            'platform_fee_amount' => $platformFees,
            'tax_amount' => $taxAmount,
            'customer_fee_amount' => $customerFeeAmount,
            'absorbed_fee_amount' => $absorbedFeeAmount,
            'currency_code' => $currency,
            'occurred_at' => $paidAt ? Carbon::parse((string) $paidAt) : ($transaction->occurred_at ?? now()),
            'meta' => array_merge((array) $transaction->meta, [
                'gateway_payload' => $payload,
                'gateway_code' => $gateway->code,
                'gateway_transaction_id' => data_get($payload, 'data.id'),
                'pricing' => $pricingSnapshot,
                'fee_breakdown' => [
                    'platform_fee_amount' => $platformFees,
                    'gateway_fee_amount' => $gatewayFees,
                    'tax_amount' => $taxAmount,
                    'customer_fee_amount' => $customerFeeAmount,
                    'absorbed_fee_amount' => $absorbedFeeAmount,
                    'total_fee_amount' => $fees,
                ],
            ]),
            'pricing_snapshot' => $pricingSnapshot,
        ])->save();

        if ($this->shouldReleaseTicketReservationForStatus($status)) {
            $this->releaseTicketReservationForTransaction($tenant, $transaction);
        }

        $order = $this->findOrderInTenant($tenant, $transaction->transaction_reference);

        Log::channel((string) config('ticket.logging.payments_channel', 'payments'))->info('public_checkout_verified', [
            'tenant_id' => $tenant->getKey(),
            'transaction_reference' => $transaction->transaction_reference,
            'gateway_reference' => $transaction->gateway_reference,
            'status' => $status,
            'gross_amount' => $amount,
            'gateway_fee_amount' => $gatewayFees,
            'order_found_before_fulfillment' => $order !== null,
        ]);

        if (PaymentStatuses::isSuccessful($status) && $order === null) {
            $payload['data']['amount'] = $amount;
            $payload['data']['fees'] = $gatewayFees;
            $payload['data']['pricing_snapshot'] = $pricingSnapshot;
            $order = $this->fulfillInTenant($tenant, $transaction->transaction_reference, $payload);

            Log::channel((string) config('ticket.logging.payments_channel', 'payments'))->info('public_checkout_fulfilled_after_verify', [
                'tenant_id' => $tenant->getKey(),
                'transaction_reference' => $transaction->transaction_reference,
                'order_reference' => $order?->reference,
                'status' => $status,
            ]);
        }

        return $this->mapVerification($transaction->fresh('paymentGateway'), $order);
    }

    private function buildPaymentMethods(?PaymentGateway $gateway, bool $isFree): array
    {
        if ($isFree) {
            return [
                ['code' => 'free', 'label' => 'Confirmation immédiate', 'kind' => 'free'],
            ];
        }

        $methods = [];

        if ($gateway?->code === 'paystack') {
            $methods[] = ['code' => 'card', 'label' => 'Carte bancaire', 'kind' => 'card'];
        }

        $providers = (array) data_get($this->platformSettingsService->grouped('payments', true), 'payments.mobile_money_providers', []);

        foreach ($providers as $code => $enabled) {
            if (! $enabled) {
                continue;
            }

            $methods[] = [
                'code' => (string) $code,
                'label' => $this->paymentMethodLabel((string) $code),
                'kind' => 'mobile_money',
            ];
        }

        return $methods;
    }

    private function shouldReleaseTicketReservationForStatus(string $status): bool
    {
        $normalized = strtolower(trim($status));

        return $normalized !== ''
            && ! PaymentStatuses::isSuccessful($normalized)
            && ! in_array($normalized, ['pending', 'ongoing', 'processing'], true);
    }

    private function releaseTicketReservationForTransaction(Tenant $tenant, PlatformTransaction $transaction): void
    {
        $meta = (array) ($transaction->meta ?? []);
        $checkout = (array) data_get($meta, 'checkout', []);

        if (data_get($checkout, 'reservation_released_at')) {
            return;
        }

        $released = $tenant->run(fn (): bool => $this->checkoutItems->release($checkout));

        if (! $released) {
            return;
        }

        data_set($meta, 'checkout.reservation_released_at', now()->toIso8601String());
        $transaction->forceFill(['meta' => $meta])->save();
    }

    private function isCrowdfundingItem(CheckoutItem $item): bool
    {
        return $item->pricingOffer instanceof Offer
            && $item->pricingOffer->offerable_type === CrowdfundingCampaign::class;
    }

    private function customContributionAmount(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return max(1, (int) $amount);
    }

    private function assertBuyerCanPurchase(
        Tenant $tenant,
        CheckoutItem $item,
        string $buyerEmail,
        ?int $buyerUserId,
        int $quantity,
    ): void {
        $offer = $item->pricingOffer;

        if (! $offer instanceof Offer) {
            return;
        }

        if ($offer->offerable_type === CrowdfundingCampaign::class) {
            return;
        }

        $maxPerAccount = (int) ($offer->max_per_account ?: 0);

        if ($maxPerAccount <= 0) {
            return;
        }

        $email = Str::lower($buyerEmail);
        $alreadyConfirmed = (int) Order::query()
            ->where('offer_id', $offer->getKey())
            ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Confirmed->value])
            ->where(function ($query) use ($buyerUserId, $email): void {
                if ($buyerUserId !== null) {
                    $query->where('buyer_user_id', $buyerUserId)
                        ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);

                    return;
                }

                $query->whereRaw('LOWER(buyer_email) = ?', [$email]);
            })
            ->sum('quantity');

        $reservedPending = PlatformTransaction::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('type', 'public_checkout')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(20))
            ->get()
            ->filter(function (PlatformTransaction $transaction) use ($offer, $buyerUserId, $email): bool {
                $checkout = (array) data_get($transaction->meta ?? [], 'checkout', []);

                if ((int) ($checkout['offer_id'] ?? 0) !== (int) $offer->getKey()) {
                    return false;
                }

                if ($buyerUserId !== null && (int) ($checkout['buyer_user_id'] ?? 0) === $buyerUserId) {
                    return true;
                }

                return Str::lower((string) ($checkout['buyer_email'] ?? '')) === $email;
            })
            ->sum(fn (PlatformTransaction $transaction): int => max(1, (int) data_get($transaction->meta ?? [], 'checkout.quantity', 1)));

        $remaining = $maxPerAccount - $alreadyConfirmed - $reservedPending;

        if ($quantity > $remaining) {
            throw new RuntimeException(sprintf(
                'Limite atteinte pour cette offre. Il reste %d place(s) disponible(s) pour ce compte.',
                max(0, $remaining),
            ));
        }
    }

    private function resolveCheckoutItemFromPayload(array $payload): CheckoutItem
    {
        $ticketIdentifier = trim((string) ($payload['ticket'] ?? ''));

        if ($ticketIdentifier !== '') {
            return $this->resolveCheckoutItem($ticketIdentifier, 'event_ticket');
        }

        return $this->resolveCheckoutItem((string) ($payload['offer'] ?? ''), 'offer');
    }

    private function resolveCheckoutItem(string $identifier, ?string $type = null): CheckoutItem
    {
        $item = $this->checkoutItems->resolve(trim($identifier), $type);

        if (! $item instanceof CheckoutItem) {
            throw new RuntimeException($type === 'event_ticket' ? 'Ticket introuvable.' : 'Offre introuvable.');
        }

        return $item;
    }

    private function moduleFromOfferableType(string $offerableType): string
    {
        return match ($offerableType) {
            'App\\Models\\Event' => CommercialModule::Ticketing->value,
            'App\\Models\\Training' => CommercialModule::Training->value,
            'App\\Models\\Stand' => CommercialModule::Stands->value,
            'App\\Models\\CallForProject' => CommercialModule::CallsForProjects->value,
            'App\\Models\\CrowdfundingCampaign' => CommercialModule::Crowdfunding->value,
            default => CommercialModule::Ticketing->value,
        };
    }

    private function paymentMethodLabel(string $code): string
    {
        return match ($code) {
            'orange_money' => 'Orange Money',
            'mtn_money' => 'MTN Money',
            'wave' => 'Wave',
            'moov_money' => 'Moov Money',
            default => Str::headline(str_replace('_', ' ', $code)),
        };
    }

    private function generateReference(string $prefix = 'PAY', ?string $currencyCode = null): string
    {
        return $this->referenceGenerator->generate($prefix, $currencyCode);
    }

    private function buildInternalSuccessPayload(string $reference, array $pricing, array $metadata): array
    {
        return [
            'status' => true,
            'message' => 'Payment confirmed internally.',
            'data' => [
                'id' => $reference,
                'reference' => $reference,
                'status' => 'success',
                'amount' => (int) ($pricing['total'] ?? 0),
                'fees' => (int) ($pricing['gateway_fee_amount'] ?? 0),
                'currency' => (string) ($pricing['currency'] ?? 'XOF'),
                'paid_at' => now()->toIso8601String(),
                'pricing_snapshot' => $pricing,
                'metadata' => $metadata,
            ],
        ];
    }

    private function fulfillInTenant(Tenant $tenant, string $reference, array $payload): ?Order
    {
        return $tenant->run(fn () => $this->orderFulfillmentService->fulfill($reference, $payload));
    }

    private function findOrderInTenant(Tenant $tenant, string $reference): ?Order
    {
        return $tenant->run(fn () => $this->orderCatalog->findByTransactionReference($reference));
    }

    private function mapVerification(PlatformTransaction $transaction, ?Order $order): array
    {
        $receipt = $order?->receipt;
        $accessPasses = $order?->accessPasses;
        $quantity = $order?->quantity ?? (int) data_get($transaction->meta ?? [], 'checkout.quantity', 1);
        $paymentMethodCode = (string) data_get($transaction->meta ?? [], 'checkout.payment_method', '');
        $paymentLabel = $transaction->gross_amount <= 0
            ? 'Confirmation immédiate'
            : ($paymentMethodCode !== '' ? $this->paymentMethodLabel($paymentMethodCode) : 'Paiement sécurisé');

        return [
            'reference' => $transaction->transaction_reference,
            'status' => (string) $transaction->status,
            'is_successful' => PaymentStatuses::isSuccessful((string) $transaction->status),
            'paid_at' => $transaction->occurred_at?->toIso8601String(),
            'quantity' => max(1, $quantity),
            'payment_label' => $paymentLabel,
            'amounts' => [
                'gross' => (int) $transaction->gross_amount,
                'fees' => (int) $transaction->customer_fee_amount,
                'net' => (int) $transaction->net_amount,
                'gateway_fee' => (int) $transaction->gateway_fee_amount,
                'platform_fee' => (int) $transaction->platform_fee_amount,
                'tax' => (int) $transaction->tax_amount,
                'customer_fee' => (int) $transaction->customer_fee_amount,
                'currency' => (string) $transaction->currency_code,
            ],
            'pricing_snapshot' => $transaction->pricing_snapshot,
            'order' => $order ? [
                'public_id' => $order->public_id,
                'reference' => $order->reference,
                'status' => $order->status?->value ?? $order->status,
            ] : null,
            'receipt' => $receipt ? [
                'public_id' => $receipt->public_id,
                'reference' => $receipt->reference,
                'status' => $receipt->status,
            ] : null,
            'access_passes_count' => $accessPasses?->count() ?? 0,
        ];
    }
}

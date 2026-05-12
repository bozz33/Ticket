<?php

namespace App\Services\Payments;

use App\Enums\AccessPassType;
use App\Enums\OrderStatus;
use App\Models\AccessPass;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Receipt;
use App\Support\References\ReferenceGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    public function __construct(
        private readonly ReferenceGenerator $referenceGenerator,
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
        $quantity = max(1, (int) Arr::get($metadata, 'quantity', 1));
        $grossAmount = (int) Arr::get($payload, 'data.amount', 0);
        $currencyCode = strtoupper((string) Arr::get($payload, 'data.currency', 'XOF'));
        $buyerName = (string) Arr::get($metadata, 'buyer_name', '');
        $buyerEmail = (string) Arr::get($metadata, 'buyer_email', '');
        $buyerPhone = (string) Arr::get($metadata, 'buyer_phone', '');
        $buyerUserId = (int) Arr::get($metadata, 'buyer_user_id', 0) ?: null;
        $gatewayReference = (string) Arr::get($payload, 'data.reference', $transactionReference);
        $gatewayTransactionId = Arr::get($payload, 'data.id');
        $paymentMethod = (string) Arr::get($metadata, 'payment_method', '');
        $pricingSnapshot = (array) Arr::get($payload, 'data.pricing_snapshot', Arr::get($metadata, 'pricing_snapshot', []));

        $offer = $offerId > 0 ? Offer::query()->find($offerId) : null;
        $unitAmount = $offer !== null ? $offer->price_amount : (int) ($grossAmount / max(1, $quantity));

        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use (
            $transactionReference,
            $offer,
            $offerId,
            $offerableType,
            $quantity,
            $unitAmount,
            $grossAmount,
            $currencyCode,
            $buyerName,
            $buyerEmail,
            $buyerPhone,
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
                    'meta' => [
                        'transaction_reference' => $transactionReference,
                        'gateway_reference' => $gatewayReference,
                        'gateway_transaction_id' => $gatewayTransactionId,
                        'offerable_type' => $offerableType,
                        'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
                    ],
                    'pricing_snapshot' => $pricingSnapshot,
                ],
            );

            $this->ensureReceipt($order);

            if (! $isCrowdfunding) {
                $this->ensureAccessPasses($order, $offer, $offerableType);
            }

            return $order->fresh(['receipt', 'accessPasses']);
        });
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

    private function ensureAccessPasses(Order $order, ?Offer $offer, string $offerableType): void
    {
        $existingCount = $order->accessPasses()->count();

        if ($existingCount >= $order->quantity) {
            return;
        }

        $passType = $offer !== null
            ? AccessPassType::fromOfferableType($offerableType)
            : AccessPassType::PurchasePass;

        $needed = $order->quantity - $existingCount;

        for ($i = 0; $i < $needed; $i++) {
            AccessPass::query()->create([
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
                ],
            ]);
        }

        if ($offer !== null) {
            $offer->increment('quantity_sold', $needed);
        }
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
}

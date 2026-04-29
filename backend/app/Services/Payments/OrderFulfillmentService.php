<?php

namespace App\Services\Payments;

use App\Enums\AccessPassType;
use App\Enums\OrderStatus;
use App\Models\AccessPass;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Receipt;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderFulfillmentService
{
    /**
     * Fulfill a confirmed payment by creating an Order, Receipt and AccessPasses.
     * Idempotent: safe to call multiple times for the same transaction_reference.
     */
    public function fulfill(string $transactionReference, array $payload): ?Order
    {
        $metadata = (array) Arr::get($payload, 'data.metadata', []);
        $offerableType = (string) Arr::get($metadata, 'offerable_type', '');

        // Crowdfunding contributions do not generate passes.
        // They still get an order + receipt for financial traceability.
        $isCrowdfunding = str_ends_with($offerableType, 'CrowdfundingCampaign');

        $offerId = (int) Arr::get($metadata, 'offer_id', 0);
        $quantity = max(1, (int) Arr::get($metadata, 'quantity', 1));
        $grossAmount = (int) Arr::get($payload, 'data.amount', 0);
        $currencyCode = strtoupper((string) Arr::get($payload, 'data.currency', 'XOF'));
        $buyerName = (string) Arr::get($metadata, 'buyer_name', '');
        $buyerEmail = (string) Arr::get($metadata, 'buyer_email', '');
        $buyerPhone = (string) Arr::get($metadata, 'buyer_phone', '');

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
            $isCrowdfunding,
            $payload,
        ): Order {
            /** @var Order $order */
            $order = Order::query()->firstOrCreate(
                ['transaction_reference' => $transactionReference],
                [
                    'reference' => $this->generateReference('ORD'),
                    'offer_id' => $offerId > 0 ? $offerId : null,
                    'status' => OrderStatus::Confirmed,
                    'quantity' => $quantity,
                    'unit_amount' => $unitAmount,
                    'total_amount' => $grossAmount,
                    'currency_code' => $currencyCode,
                    'buyer_name' => $buyerName ?: null,
                    'buyer_email' => $buyerEmail ?: null,
                    'buyer_phone' => $buyerPhone ?: null,
                    'meta' => [
                        'paystack_reference' => Arr::get($payload, 'data.reference'),
                        'offerable_type' => $offerableType,
                    ],
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

        Receipt::query()->create([
            'order_id' => $order->id,
            'reference' => $this->generateReference('RCP'),
            'status' => 'issued',
            'total_amount' => $order->total_amount,
            'currency_code' => $order->currency_code,
            'buyer_name' => $order->buyer_name,
            'buyer_email' => $order->buyer_email,
            'issued_at' => now(),
            'meta' => [
                'order_reference' => $order->reference,
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

    private function generateReference(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, strtoupper(date('ymd')), strtoupper(Str::random(6)));
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
}

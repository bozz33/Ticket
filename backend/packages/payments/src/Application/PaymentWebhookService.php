<?php

namespace Ticket\Payments\Application;

use App\Models\GatewayWebhookLog;
use App\Models\IncidentLog;
use App\Models\PaymentGateway;
use App\Models\PaymentIncident;
use App\Models\PlatformTransaction;
use App\Support\Payments\GatewayAmountConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;
use Ticket\Payments\Domain\PaymentStatuses;

class PaymentWebhookService
{
    public function __construct(
        private readonly OrderFulfillmentService $orderFulfillmentService,
        private readonly PaymentGatewayCredentialResolver $credentialResolver,
        private readonly FinancePolicyCatalog $financePolicyService,
        private readonly GatewayAmountConverter $amountConverter,
    ) {}

    public function receive(PaymentGateway $gateway, Request $request): GatewayWebhookLog
    {
        $payload = $request->all();
        $transaction = $this->findTransactionFromPayload($payload);
        $headers = collect($request->headers->all())
            ->map(fn (array $values) => count($values) === 1 ? $values[0] : $values)
            ->all();

        $status = 'received';
        $responseCode = 202;
        $failureReason = null;

        try {
            $this->verifySignature($gateway, $request);
            $status = 'processed';
            $transaction = $this->syncTransaction($gateway, $payload) ?? $transaction;
            $this->fulfillSuccessfulTransaction($transaction, $payload);

            Log::channel((string) config('ticket.logging.payments_channel', 'payments'))->info('payment_webhook_processed', [
                'gateway_code' => $gateway->code,
                'reference' => Arr::get($payload, 'data.reference'),
                'event_name' => Arr::get($payload, 'event', 'unknown'),
                'tenant_id' => $transaction?->tenant_id,
                'platform_transaction_id' => $transaction?->getKey(),
            ]);
        } catch (\Throwable $exception) {
            $status = 'failed';
            $responseCode = 400;
            $failureReason = $exception->getMessage();
            $this->recordFailureIncident($gateway, $transaction, $payload, $failureReason);

            Log::channel((string) config('ticket.logging.security_channel', 'security'))->warning('payment_webhook_failed', [
                'gateway_code' => $gateway->code,
                'reference' => Arr::get($payload, 'data.reference'),
                'event_name' => Arr::get($payload, 'event', 'unknown'),
                'tenant_id' => $transaction?->tenant_id,
                'platform_transaction_id' => $transaction?->getKey(),
                'failure_reason' => $failureReason,
            ]);
        }

        return GatewayWebhookLog::query()->create([
            'payment_gateway_id' => $gateway->getKey(),
            'tenant_id' => $transaction?->tenant_id,
            'platform_transaction_id' => $transaction?->getKey(),
            'event_name' => Arr::get($payload, 'event', 'unknown'),
            'external_id' => (string) (Arr::get($payload, 'data.id') ?? Arr::get($payload, 'data.reference') ?? Arr::get($payload, 'id') ?? ''),
            'signature' => $request->header('x-paystack-signature') ?? $request->header('x-signature'),
            'headers' => $headers,
            'payload' => $payload,
            'status' => $status,
            'response_code' => $responseCode,
            'failure_reason' => $failureReason,
            'processed_at' => now(),
            'attempt_count' => 1,
            'meta' => [
                'gateway_code' => $gateway->code,
            ],
        ]);
    }

    protected function verifySignature(PaymentGateway $gateway, Request $request): void
    {
        if ($gateway->code === 'paystack') {
            $this->verifyPaystackSignature($gateway, $request);

            return;
        }

        // Fail closed: never accept a webhook from a gateway whose signature scheme is
        // unknown. Silently skipping verification would let a forged payload trigger
        // order fulfillment. Adding a new gateway must include an explicit verifier.
        throw new \RuntimeException(sprintf(
            'Vérification de signature non supportée pour la passerelle "%s".',
            $gateway->code,
        ));
    }

    protected function verifyPaystackSignature(PaymentGateway $gateway, Request $request): void
    {
        $secret = $this->credentialResolver->webhookSecret($gateway);
        $signature = $request->header('x-paystack-signature');

        if (blank($secret) || blank($signature)) {
            throw new \RuntimeException('Signature Paystack manquante.');
        }

        $computed = hash_hmac('sha512', $request->getContent(), $secret);

        if (! hash_equals($computed, $signature)) {
            throw new \RuntimeException('Signature Paystack invalide.');
        }
    }

    protected function syncTransaction(PaymentGateway $gateway, array $payload): ?PlatformTransaction
    {
        $reference = Arr::get($payload, 'data.reference');

        if (blank($reference)) {
            return null;
        }

        $existingTransaction = PlatformTransaction::query()
            ->where('transaction_reference', (string) $reference)
            ->first();

        $existingMeta = (array) ($existingTransaction?->meta ?? []);
        $pricingSnapshot = $this->financePolicyService->applyGatewayFeeToSnapshot(
            (array) ($existingTransaction?->pricing_snapshot ?? data_get($existingMeta, 'pricing', [])),
            $this->amountConverter->fromGateway(
                (int) Arr::get($payload, 'data.fees', 0),
                strtoupper((string) Arr::get($payload, 'data.currency', $existingTransaction?->currency_code ?? 'XOF')),
                $gateway->code,
            ),
        );
        $currencyCode = strtoupper((string) Arr::get($payload, 'data.currency', $existingTransaction?->currency_code ?? 'XOF'));
        $amount = $this->amountConverter->fromGateway(
            (int) Arr::get($payload, 'data.amount', 0),
            $currencyCode,
            $gateway->code,
        );
        $gatewayFees = $this->amountConverter->fromGateway(
            (int) Arr::get($payload, 'data.fees', 0),
            $currencyCode,
            $gateway->code,
        );
        $platformFees = (int) data_get($pricingSnapshot, 'platform_fee_amount', $existingTransaction?->platform_fee_amount ?? 0);
        $taxAmount = (int) data_get($pricingSnapshot, 'tax_amount', $existingTransaction?->tax_amount ?? 0);
        $customerFeeAmount = (int) data_get($pricingSnapshot, 'customer_fee_total', $existingTransaction?->customer_fee_amount ?? 0);
        $absorbedFeeAmount = (int) data_get($pricingSnapshot, 'absorbed_fee_total', $existingTransaction?->absorbed_fee_amount ?? 0);
        $organizerNet = (int) data_get($pricingSnapshot, 'organizer_net', $existingTransaction?->net_amount ?? max(0, $amount - $gatewayFees - $platformFees - $taxAmount));
        $totalFees = (int) data_get($pricingSnapshot, 'total_fee_amount', $platformFees + $customerFeeAmount + $taxAmount);
        $mergedMeta = array_merge($existingMeta, [
            'gateway_payload' => $payload,
            'gateway_code' => $gateway->code,
            'gateway_transaction_id' => Arr::get($payload, 'data.id'),
            'pricing' => $pricingSnapshot,
            'fee_breakdown' => [
                'platform_fee_amount' => $platformFees,
                'gateway_fee_amount' => $gatewayFees,
                'tax_amount' => $taxAmount,
                'customer_fee_amount' => $customerFeeAmount,
                'absorbed_fee_amount' => $absorbedFeeAmount,
                'total_fee_amount' => $totalFees,
            ],
        ]);

        return PlatformTransaction::query()->updateOrCreate(
            [
                'transaction_reference' => (string) $reference,
            ],
            [
                'tenant_id' => $existingTransaction?->tenant_id,
                'payment_gateway_id' => $gateway->getKey(),
                'gateway_reference' => (string) (Arr::get($payload, 'data.reference') ?? $reference),
                'type' => $existingTransaction?->type ?? 'gateway_charge',
                'direction' => $existingTransaction?->direction ?? 'credit',
                'status' => (string) (Arr::get($payload, 'data.status') ?? Arr::get($payload, 'event') ?? 'processed'),
                'gross_amount' => $amount,
                'fee_amount' => $totalFees,
                'net_amount' => $organizerNet,
                'gateway_fee_amount' => $gatewayFees,
                'platform_fee_amount' => $platformFees,
                'tax_amount' => $taxAmount,
                'customer_fee_amount' => $customerFeeAmount,
                'absorbed_fee_amount' => $absorbedFeeAmount,
                'currency_code' => $currencyCode,
                'occurred_at' => now(),
                'meta' => $mergedMeta,
                'pricing_snapshot' => $pricingSnapshot,
            ],
        );
    }

    protected function fulfillSuccessfulTransaction(?PlatformTransaction $transaction, array $payload): void
    {
        if (! $transaction || ! PaymentStatuses::isSuccessful((string) $transaction->status)) {
            return;
        }

        if ($transaction->type !== 'public_checkout') {
            return;
        }

        $transaction->loadMissing('tenant');
        $tenant = $transaction->tenant;

        if (! $tenant) {
            return;
        }

        $tenant->run(fn () => $this->orderFulfillmentService->fulfill(
            $transaction->transaction_reference,
            $this->buildFulfillmentPayload($transaction, $payload),
        ));
    }

    protected function buildFulfillmentPayload(PlatformTransaction $transaction, array $payload): array
    {
        $checkout = (array) data_get($transaction->meta ?? [], 'checkout', []);
        // Server-side checkout data takes precedence over webhook-supplied metadata to
        // prevent metadata injection: an attacker cannot override offer_id, quantity, or
        // buyer_email by crafting a malicious webhook payload, even with a valid signature.
        $metadata = array_replace((array) Arr::get($payload, 'data.metadata', []), $checkout);

        return [
            'data' => [
                'id' => Arr::get($payload, 'data.id', $transaction->gateway_reference ?? $transaction->transaction_reference),
                'reference' => Arr::get($payload, 'data.reference', $transaction->transaction_reference),
                'status' => Arr::get($payload, 'data.status', $transaction->status),
                'amount' => $this->amountConverter->fromGateway(
                    (int) Arr::get($payload, 'data.amount', $transaction->gross_amount),
                    strtoupper((string) Arr::get($payload, 'data.currency', $transaction->currency_code ?? 'XOF')),
                    (string) data_get($transaction->meta ?? [], 'gateway_code', $transaction->paymentGateway?->code),
                ),
                'fees' => $this->amountConverter->fromGateway(
                    (int) Arr::get($payload, 'data.fees', $transaction->gateway_fee_amount),
                    strtoupper((string) Arr::get($payload, 'data.currency', $transaction->currency_code ?? 'XOF')),
                    (string) data_get($transaction->meta ?? [], 'gateway_code', $transaction->paymentGateway?->code),
                ),
                'currency' => strtoupper((string) Arr::get($payload, 'data.currency', $transaction->currency_code ?? 'XOF')),
                'pricing_snapshot' => $transaction->pricing_snapshot,
                'metadata' => $metadata,
            ],
        ];
    }

    protected function findTransactionFromPayload(array $payload): ?PlatformTransaction
    {
        $reference = (string) Arr::get($payload, 'data.reference', '');

        if ($reference === '') {
            return null;
        }

        return PlatformTransaction::query()
            ->where('transaction_reference', $reference)
            ->first();
    }

    protected function recordFailureIncident(
        PaymentGateway $gateway,
        ?PlatformTransaction $transaction,
        array $payload,
        string $failureReason,
    ): void {
        $incident = PaymentIncident::query()->create([
            'tenant_id' => $transaction?->tenant_id,
            'platform_transaction_id' => $transaction?->getKey(),
            'payment_gateway_id' => $gateway->getKey(),
            'severity' => 'high',
            'status' => 'open',
            'incident_code' => 'payment_webhook_failed',
            'summary' => $failureReason,
            'detected_at' => now(),
            'meta' => [
                'event_name' => Arr::get($payload, 'event', 'unknown'),
                'reference' => Arr::get($payload, 'data.reference'),
                'gateway_code' => $gateway->code,
            ],
        ]);

        IncidentLog::query()->create([
            'tenant_id' => $transaction?->tenant_id,
            'payment_incident_id' => $incident->getKey(),
            'title' => 'Échec de traitement webhook paiement',
            'severity' => 'high',
            'status' => 'open',
            'incident_type' => 'payment_webhook',
            'summary' => $failureReason,
            'detected_at' => now(),
            'meta' => [
                'event_name' => Arr::get($payload, 'event', 'unknown'),
                'reference' => Arr::get($payload, 'data.reference'),
                'gateway_code' => $gateway->code,
            ],
        ]);
    }
}

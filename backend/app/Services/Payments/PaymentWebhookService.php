<?php

namespace App\Services\Payments;

use App\Models\GatewayWebhookLog;
use App\Models\PaymentGateway;
use App\Models\PlatformTransaction;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService
{
    public function __construct(private readonly OrderFulfillmentService $fulfillmentService) {}

    public function receive(PaymentGateway $gateway, Request $request): GatewayWebhookLog
    {
        $payload = $request->all();
        $headers = collect($request->headers->all())
            ->map(fn (array $values) => count($values) === 1 ? $values[0] : $values)
            ->all();

        $status = 'received';
        $responseCode = 202;
        $failureReason = null;

        try {
            $this->verifySignature($gateway, $request);
            $transaction = $this->syncTransaction($gateway, $payload);
            $this->triggerFulfillment($transaction, $payload);
            $status = 'processed';
        } catch (\Throwable $exception) {
            $status = 'failed';
            $responseCode = 400;
            $failureReason = $exception->getMessage();
        }

        return GatewayWebhookLog::query()->create([
            'payment_gateway_id' => $gateway->getKey(),
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
        if ($gateway->code !== 'paystack') {
            return;
        }

        $secret = $gateway->webhook_secret ?: $gateway->secret_key;
        $signature = $request->header('x-paystack-signature');

        if (blank($secret) || blank($signature)) {
            throw new \RuntimeException('Signature Paystack manquante.');
        }

        $computed = hash_hmac('sha512', $request->getContent(), $secret);

        if (! hash_equals($computed, $signature)) {
            throw new \RuntimeException('Signature Paystack invalide.');
        }
    }

    protected function syncTransaction(PaymentGateway $gateway, array $payload): PlatformTransaction
    {
        $reference = Arr::get($payload, 'data.reference');

        if (blank($reference)) {
            throw new \RuntimeException('Référence de transaction manquante dans le payload.');
        }

        $tenantId = Arr::get($payload, 'data.metadata.tenant_id');

        return PlatformTransaction::query()->updateOrCreate(
            ['transaction_reference' => (string) $reference],
            [
                'payment_gateway_id' => $gateway->getKey(),
                'tenant_id' => $tenantId,
                'gateway_reference' => (string) (Arr::get($payload, 'data.id') ?? $reference),
                'type' => 'gateway_charge',
                'direction' => 'credit',
                'status' => (string) (Arr::get($payload, 'data.status') ?? Arr::get($payload, 'event') ?? 'processed'),
                'gross_amount' => (int) Arr::get($payload, 'data.amount', 0),
                'fee_amount' => (int) Arr::get($payload, 'data.fees', 0),
                'net_amount' => (int) Arr::get($payload, 'data.amount', 0) - (int) Arr::get($payload, 'data.fees', 0),
                'currency_code' => strtoupper((string) Arr::get($payload, 'data.currency', 'XOF')),
                'occurred_at' => now(),
                'meta' => $payload,
            ],
        );
    }

    protected function triggerFulfillment(PlatformTransaction $transaction, array $payload): void
    {
        $event = Arr::get($payload, 'event', '');
        $status = Arr::get($payload, 'data.status', '');

        if ($event !== 'charge.success' && $status !== 'success') {
            return;
        }

        if ($transaction->tenant_id === null) {
            return;
        }

        $tenant = Tenant::query()->find($transaction->tenant_id);

        if ($tenant === null) {
            Log::warning('OrderFulfillment: tenant introuvable', ['tenant_id' => $transaction->tenant_id]);

            return;
        }

        $tenant->run(function () use ($transaction, $payload): void {
            try {
                $this->fulfillmentService->fulfill($transaction->transaction_reference, $payload);
            } catch (\Throwable $exception) {
                Log::error('OrderFulfillment: échec du fulfillment', [
                    'transaction_reference' => $transaction->transaction_reference,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}

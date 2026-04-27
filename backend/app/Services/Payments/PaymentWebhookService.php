<?php

namespace App\Services\Payments;

use App\Models\GatewayWebhookLog;
use App\Models\PaymentGateway;
use App\Models\PlatformTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PaymentWebhookService
{
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
            $status = 'processed';
            $this->syncTransaction($gateway, $payload);
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

    protected function syncTransaction(PaymentGateway $gateway, array $payload): void
    {
        $reference = Arr::get($payload, 'data.reference');

        if (blank($reference)) {
            return;
        }

        PlatformTransaction::query()->updateOrCreate(
            [
                'transaction_reference' => (string) $reference,
            ],
            [
                'payment_gateway_id' => $gateway->getKey(),
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
}

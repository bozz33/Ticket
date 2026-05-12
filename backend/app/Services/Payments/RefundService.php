<?php

namespace App\Services\Payments;

use App\Enums\FeeChargeBearer;
use App\Enums\RefundFeeBehavior;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PlatformTransaction;
use App\Models\PlatformUser;
use App\Models\Refund;
use App\Models\Tenant;
use App\Support\Payments\GatewayAmountConverter;
use App\Support\References\ReferenceGenerator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class RefundService
{
    public function __construct(
        private readonly PaymentGatewayCredentialResolver $credentialResolver,
        private readonly TenantRefundService $tenantRefundService,
        private readonly PaymentGatewayHttpClientFactory $httpClientFactory,
        private readonly GatewayAmountConverter $amountConverter,
        private readonly ReferenceGenerator $referenceGenerator,
    ) {}

    public function quote(PlatformTransaction $transaction): array
    {
        $pricingSnapshot = (array) ($transaction->pricing_snapshot ?? []);
        $subtotal = (int) ($pricingSnapshot['subtotal'] ?? max(0, (int) $transaction->gross_amount - (int) $transaction->customer_fee_amount));
        $platform = $this->componentSummary(
            (array) data_get($pricingSnapshot, 'breakdown.platform_fee', []),
            (int) $transaction->platform_fee_amount,
        );
        $gateway = $this->componentSummary(
            (array) data_get($pricingSnapshot, 'breakdown.gateway_fee', []),
            (int) $transaction->gateway_fee_amount,
        );

        $customerRefund = $subtotal;

        if ($platform['charge_bearer'] === FeeChargeBearer::Buyer->value && $platform['is_refundable']) {
            $customerRefund += $platform['total'];
        }

        if ($gateway['charge_bearer'] === FeeChargeBearer::Buyer->value && $gateway['is_refundable']) {
            $customerRefund += $gateway['total'];
        }

        $organizerReversal = $subtotal;

        if ($platform['charge_bearer'] === FeeChargeBearer::Organizer->value && ! $platform['is_refundable']) {
            $organizerReversal -= $platform['total'];
        }

        if ($gateway['charge_bearer'] === FeeChargeBearer::Organizer->value && ! $gateway['is_refundable']) {
            $organizerReversal -= $gateway['total'];
        }

        $platformFeeRetained = $platform['is_refundable'] ? 0 : $platform['base_amount'];
        $gatewayFeeRetained = $gateway['is_refundable'] ? 0 : $gateway['base_amount'];
        $taxRetained = ($platform['is_refundable'] ? 0 : $platform['tax_amount'])
            + ($gateway['is_refundable'] ? 0 : $gateway['tax_amount']);

        $platformFeeReversed = $platform['is_refundable'] ? $platform['base_amount'] : 0;
        $gatewayFeeReversed = $gateway['is_refundable'] ? $gateway['base_amount'] : 0;
        $taxReversed = ($platform['is_refundable'] ? $platform['tax_amount'] : 0)
            + ($gateway['is_refundable'] ? $gateway['tax_amount'] : 0);

        return [
            'original_transaction_reference' => $transaction->transaction_reference,
            'subtotal_amount' => max(0, $subtotal),
            'customer_refund_amount' => max(0, $customerRefund),
            'organizer_reversal_amount' => max(0, $organizerReversal),
            'platform_absorption_amount' => max(0, $customerRefund - max(0, $organizerReversal)),
            'platform_fee_retained' => $platformFeeRetained,
            'gateway_fee_retained' => $gatewayFeeRetained,
            'tax_retained' => $taxRetained,
            'platform_fee_reversed' => $platformFeeReversed,
            'gateway_fee_reversed' => $gatewayFeeReversed,
            'tax_reversed' => $taxReversed,
            'customer_fee_refunded' => max(0, $customerRefund - $subtotal),
            'currency_code' => strtoupper((string) ($transaction->currency_code ?: 'XOF')),
            'components' => [
                'platform' => $platform,
                'gateway' => $gateway,
            ],
            'pricing_snapshot' => $pricingSnapshot,
        ];
    }

    public function create(PlatformTransaction $transaction, array $payload = [], ?PlatformUser $actor = null): Refund
    {
        $transaction->loadMissing(['tenant', 'paymentGateway', 'refunds']);

        $this->assertTransactionCanBeRefunded($transaction);
        $quote = $this->quote($transaction);
        $reasonCode = (string) ($payload['reason_code'] ?? 'manual_refund');
        $reason = trim((string) ($payload['reason'] ?? ''));
        $reference = $this->generateReference((string) $quote['currency_code']);
        $gateway = $transaction->paymentGateway;

        $gatewayPayload = null;
        $status = RefundStatus::Refunded;
        $gatewayStatus = null;
        $processedAt = now();

        if ($gateway !== null && (int) $transaction->gross_amount > 0) {
            $gatewayPayload = $this->createGatewayRefund($transaction, $gateway, $quote, $reasonCode, $reason, $actor);
            $gatewayStatus = (string) data_get($gatewayPayload, 'status');
            $status = $this->mapGatewayRefundStatus($gatewayStatus);
            $processedAt = $status === RefundStatus::Refunded
                ? Carbon::parse((string) (data_get($gatewayPayload, 'refunded_at') ?? now()))
                : now();
        }

        $refund = Refund::query()->create([
            'tenant_id' => $transaction->tenant_id,
            'platform_transaction_id' => $transaction->getKey(),
            'payment_gateway_id' => $gateway?->getKey(),
            'reference' => $reference,
            'gateway_refund_id' => data_get($gatewayPayload, 'id') ? (string) data_get($gatewayPayload, 'id') : null,
            'gateway_refund_reference' => (string) (data_get($gatewayPayload, 'transaction.reference') ?? $transaction->gateway_reference ?? $transaction->transaction_reference),
            'status' => $status,
            'gateway_status' => $gatewayStatus,
            'reason_code' => $reasonCode,
            'reason' => $reason !== '' ? $reason : null,
            'amount_requested' => (int) $quote['customer_refund_amount'],
            'amount_refunded_to_buyer' => (int) $quote['customer_refund_amount'],
            'organizer_reversal_amount' => (int) $quote['organizer_reversal_amount'],
            'platform_absorption_amount' => (int) $quote['platform_absorption_amount'],
            'gateway_fee_retained' => (int) $quote['gateway_fee_retained'],
            'platform_fee_retained' => (int) $quote['platform_fee_retained'],
            'tax_retained' => (int) $quote['tax_retained'],
            'currency_code' => (string) $quote['currency_code'],
            'processed_at' => $processedAt,
            'meta' => [
                'gateway_payload' => $gatewayPayload,
                'actor_email' => $actor?->email,
                'refund_quote' => $quote,
            ],
            'pricing_snapshot' => $quote,
        ]);

        $this->createOrUpdateRefundLedgerTransaction($refund, $transaction, $quote);
        $this->syncTenantState($refund, $transaction->tenant);

        return $refund->fresh(['tenant', 'transaction', 'paymentGateway']);
    }

    public function sync(Refund $refund): Refund
    {
        $refund->loadMissing(['paymentGateway', 'tenant', 'transaction']);

        $gateway = $refund->paymentGateway;

        if (! $gateway || blank($refund->gateway_refund_id)) {
            return $refund;
        }

        $payload = match ($gateway->code) {
            'paystack' => $this->fetchPaystackRefund($gateway, (string) $refund->gateway_refund_id),
            default => throw new \RuntimeException('Gateway de remboursement non prise en charge.'),
        };

        $gatewayStatus = (string) data_get($payload, 'status');
        $status = $this->mapGatewayRefundStatus($gatewayStatus);

        $refund->forceFill([
            'status' => $status,
            'gateway_status' => $gatewayStatus,
            'processed_at' => $status === RefundStatus::Refunded
                ? Carbon::parse((string) (data_get($payload, 'refunded_at') ?? data_get($payload, 'updatedAt') ?? now()))
                : $refund->processed_at,
            'meta' => array_merge((array) ($refund->meta ?? []), [
                'gateway_sync_payload' => $payload,
            ]),
        ])->save();

        $quote = (array) ($refund->pricing_snapshot ?? []);
        $transaction = $refund->transaction;

        if ($transaction) {
            $this->createOrUpdateRefundLedgerTransaction($refund, $transaction, $quote);
        }

        $this->syncTenantState($refund, $refund->tenant);

        return $refund->fresh(['tenant', 'transaction', 'paymentGateway']);
    }

    private function assertTransactionCanBeRefunded(PlatformTransaction $transaction): void
    {
        if ($transaction->direction !== 'credit') {
            throw new \RuntimeException('Seules les transactions crédit peuvent être remboursées.');
        }

        if (! $this->isSuccessfulTransactionStatus((string) $transaction->status)) {
            throw new \RuntimeException('La transaction n’est pas dans un état remboursable.');
        }

        if ((int) $transaction->gross_amount <= 0) {
            throw new \RuntimeException('Aucun remboursement monétaire n’est possible sur cette transaction.');
        }

        $existingRefund = Refund::query()
            ->where('platform_transaction_id', $transaction->getKey())
            ->whereIn('status', [
                RefundStatus::Pending->value,
                RefundStatus::Processing->value,
                RefundStatus::Refunded->value,
            ])
            ->exists();

        if ($existingRefund) {
            throw new \RuntimeException('Un remboursement est déjà en cours ou terminé pour cette transaction.');
        }

        $tenant = $transaction->tenant;

        if (! $tenant) {
            throw new \RuntimeException('Le tenant lié à la transaction est introuvable.');
        }

        $order = $this->resolveTenantOrder($tenant, $transaction->transaction_reference);

        if (! $order) {
            throw new \RuntimeException('La commande liée à cette transaction est introuvable.');
        }

        $this->tenantRefundService->assertOrderCanBeRefunded($order);
    }

    private function createGatewayRefund(
        PlatformTransaction $transaction,
        PaymentGateway $gateway,
        array $quote,
        string $reasonCode,
        string $reason,
        ?PlatformUser $actor,
    ): array {
        return match ($gateway->code) {
            'paystack' => $this->createPaystackRefund($transaction, $gateway, $quote, $reasonCode, $reason, $actor),
            default => throw new \RuntimeException('Gateway de remboursement non prise en charge.'),
        };
    }

    private function createPaystackRefund(
        PlatformTransaction $transaction,
        PaymentGateway $gateway,
        array $quote,
        string $reasonCode,
        string $reason,
        ?PlatformUser $actor,
    ): array {
        $secretKey = $this->credentialResolver->secretKey($gateway);

        if (blank($secretKey)) {
            throw new \RuntimeException('La configuration Paystack est incomplète pour les remboursements.');
        }

        $this->credentialResolver->assertPaystackKeyMatchesMode($gateway, $secretKey);

        $transactionIdentifier = $transaction->gateway_reference ?: $transaction->transaction_reference;
        $merchantNote = trim(sprintf(
            '[%s] %s%s',
            $reasonCode,
            $reason !== '' ? $reason : 'Refund initiated from Ticket.',
            $actor?->email ? sprintf(' by %s', $actor->email) : '',
        ));

        try {
            $response = $this->httpClientFactory
                ->forGateway($gateway)
                ->withToken($secretKey)
                ->post('https://api.paystack.co/refund', [
                    'transaction' => $transactionIdentifier,
                    'amount' => $this->amountConverter->toGateway(
                        (int) $quote['customer_refund_amount'],
                        (string) $quote['currency_code'],
                        $gateway->code,
                    ),
                    'currency' => (string) $quote['currency_code'],
                    'customer_note' => $reason !== '' ? $reason : 'Refund initiated from Ticket.',
                    'merchant_note' => $merchantNote,
                ]);
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Connexion à Paystack impossible. Vérifiez la connectivité sortante ou la configuration proxy.', previous: $exception);
        }

        $payload = $response->json();

        if (! $response->successful() || ! data_get($payload, 'status')) {
            throw new \RuntimeException((string) (data_get($payload, 'message') ?? 'Initialisation du remboursement impossible.'));
        }

        return (array) data_get($payload, 'data', []);
    }

    private function fetchPaystackRefund(PaymentGateway $gateway, string $refundId): array
    {
        $secretKey = $this->credentialResolver->secretKey($gateway);

        if (blank($secretKey)) {
            throw new \RuntimeException('La configuration Paystack est incomplète pour la synchronisation des remboursements.');
        }

        $this->credentialResolver->assertPaystackKeyMatchesMode($gateway, $secretKey);

        try {
            $response = $this->httpClientFactory
                ->forGateway($gateway)
                ->withToken($secretKey)
                ->get(sprintf('https://api.paystack.co/refund/%s', $refundId));
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Connexion à Paystack impossible. Vérifiez la connectivité sortante ou la configuration proxy.', previous: $exception);
        }

        $payload = $response->json();

        if (! $response->successful() || ! data_get($payload, 'status')) {
            throw new \RuntimeException((string) (data_get($payload, 'message') ?? 'Synchronisation du remboursement impossible.'));
        }

        return (array) data_get($payload, 'data', []);
    }

    private function mapGatewayRefundStatus(?string $gatewayStatus): RefundStatus
    {
        return match (strtolower((string) $gatewayStatus)) {
            'processed' => RefundStatus::Refunded,
            'failed' => RefundStatus::Failed,
            'rejected', 'cancelled' => RefundStatus::Rejected,
            'pending' => RefundStatus::Pending,
            default => RefundStatus::Processing,
        };
    }

    private function createOrUpdateRefundLedgerTransaction(Refund $refund, PlatformTransaction $originalTransaction, array $quote): PlatformTransaction
    {
        return PlatformTransaction::query()->updateOrCreate(
            ['transaction_reference' => $refund->reference],
            [
                'tenant_id' => $originalTransaction->tenant_id,
                'payment_gateway_id' => $originalTransaction->payment_gateway_id,
                'gateway_reference' => $refund->gateway_refund_reference ?: $originalTransaction->gateway_reference,
                'type' => 'refund',
                'direction' => 'debit',
                'status' => $this->mapRefundStatusToTransactionStatus($refund->status),
                'gross_amount' => (int) ($quote['customer_refund_amount'] ?? $refund->amount_refunded_to_buyer),
                'fee_amount' => (int) (($quote['platform_fee_reversed'] ?? 0) + ($quote['gateway_fee_reversed'] ?? 0) + ($quote['tax_reversed'] ?? 0)),
                'net_amount' => (int) ($quote['organizer_reversal_amount'] ?? $refund->organizer_reversal_amount),
                'gateway_fee_amount' => (int) ($quote['gateway_fee_reversed'] ?? 0),
                'platform_fee_amount' => (int) ($quote['platform_fee_reversed'] ?? 0),
                'tax_amount' => (int) ($quote['tax_reversed'] ?? 0),
                'payout_fee_amount' => 0,
                'customer_fee_amount' => (int) ($quote['customer_fee_refunded'] ?? 0),
                'absorbed_fee_amount' => (int) ($quote['platform_absorption_amount'] ?? $refund->platform_absorption_amount),
                'currency_code' => (string) ($quote['currency_code'] ?? $refund->currency_code),
                'occurred_at' => $refund->processed_at ?? now(),
                'meta' => [
                    'refund_reference' => $refund->reference,
                    'refund_status' => $refund->status?->value ?? $refund->status,
                    'original_transaction_reference' => $originalTransaction->transaction_reference,
                    'refund_reason_code' => $refund->reason_code,
                ],
                'pricing_snapshot' => $quote,
            ],
        );
    }

    private function syncTenantState(Refund $refund, ?Tenant $tenant): void
    {
        if (! $tenant) {
            return;
        }

        $order = $this->resolveTenantOrder($tenant, $refund->transaction?->transaction_reference ?? '');

        if (! $order) {
            return;
        }

        $tenant->run(fn () => $this->tenantRefundService->apply($refund, $order));
    }

    private function resolveTenantOrder(Tenant $tenant, string $transactionReference): ?Order
    {
        if ($transactionReference === '') {
            return null;
        }

        return $tenant->run(fn () => Order::query()
            ->with(['receipt', 'accessPasses'])
            ->where('transaction_reference', $transactionReference)
            ->first());
    }

    private function componentSummary(array $component, int $fallbackBaseAmount): array
    {
        $baseAmount = (int) ($component['base_amount'] ?? $fallbackBaseAmount);
        $taxAmount = (int) ($component['tax_amount'] ?? 0);
        $total = (int) ($component['total'] ?? ($baseAmount + $taxAmount));
        $chargeBearer = (string) ($component['charge_bearer'] ?? FeeChargeBearer::Organizer->value);
        $refundBehavior = (string) data_get($component, 'rule.refund_behavior', RefundFeeBehavior::Refundable->value);

        return [
            'base_amount' => $baseAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'charge_bearer' => $chargeBearer,
            'refund_behavior' => $refundBehavior,
            'is_refundable' => $refundBehavior === RefundFeeBehavior::Refundable->value,
            'rule' => (array) ($component['rule'] ?? []),
        ];
    }

    private function generateReference(?string $currencyCode = null): string
    {
        return $this->referenceGenerator->generate('RFD', $currencyCode);
    }

    private function isSuccessfulTransactionStatus(string $status): bool
    {
        return in_array(strtolower($status), ['success', 'successful', 'confirmed', 'completed', 'paid'], true);
    }

    private function mapRefundStatusToTransactionStatus(RefundStatus|string|null $status): string
    {
        $value = $status instanceof RefundStatus ? $status : RefundStatus::from((string) $status);

        return match ($value) {
            RefundStatus::Refunded => 'success',
            RefundStatus::Failed => 'failed',
            RefundStatus::Rejected => 'cancelled',
            RefundStatus::Pending,
            RefundStatus::Processing => 'processing',
        };
    }
}

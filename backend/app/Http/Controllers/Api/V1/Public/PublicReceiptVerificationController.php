<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Ticket\Ticketing\Contracts\ReceiptCatalog;

class PublicReceiptVerificationController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReceiptCatalog $receiptCatalog,
    ) {}

    public function show(string $tenant, string $receipt): JsonResponse
    {
        $record = $this->receiptCatalog->findByIdentifier($receipt);

        if ($record === null) {
            return response()->json(['message' => 'Reçu introuvable.'], 404);
        }

        $record->loadMissing(['order.accessPasses', 'order.offer']);

        $buyerPhone = (string) ($record->order?->buyer_phone ?? '');
        $buyerEmail = (string) ($record->buyer_email ?? '');
        $gatewayReference = (string) data_get($record->meta ?? [], 'gateway_reference', $record->order?->transaction_reference);
        $gatewayTransactionId = data_get($record->meta ?? [], 'gateway_transaction_id');

        return response()->json([
            'tenant' => $this->tenantContext->get()?->only(['id', 'public_id', 'name', 'slug']),
            'data' => [
                'public_id' => $record->public_id,
                'reference' => $record->reference,
                'status' => $record->status,
                'total_amount' => (int) $record->total_amount,
                'currency_code' => (string) $record->currency_code,
                'issued_at' => $record->issued_at?->toIso8601String(),
                'buyer_name' => $record->buyer_name,
                'buyer_email_masked' => $this->maskEmail($buyerEmail),
                'buyer_phone_masked' => $this->maskPhone($buyerPhone),
                'order_reference' => $record->order?->reference,
                'transaction_reference' => $record->order?->transaction_reference,
                'gateway_reference' => $gatewayReference,
                'gateway_transaction_id' => $gatewayTransactionId,
                'offer_name' => $record->order?->offer?->name,
                'access_passes_count' => $record->order?->accessPasses?->count() ?? 0,
            ],
        ]);
    }

    private function maskEmail(string $email): ?string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        [$localPart, $domain] = explode('@', Str::lower($email), 2);

        if ($localPart === '') {
            return null;
        }

        $visible = Str::substr($localPart, 0, min(2, Str::length($localPart)));
        $masked = $visible.str_repeat('*', max(2, Str::length($localPart) - Str::length($visible)));

        return sprintf('%s@%s', $masked, $domain);
    }

    private function maskPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '');

        if ($digits === '') {
            return null;
        }

        $suffix = Str::substr($digits, -4);

        return sprintf('*** *** %s', $suffix);
    }
}

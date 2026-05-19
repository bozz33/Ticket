<?php

declare(strict_types=1);

use App\Models\AccessPass;
use App\Models\CallForProject;
use App\Models\CallForProjectSubmission;
use App\Models\Offer;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PlatformTransaction;
use App\Models\Receipt;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\OrderFulfillmentService;
use App\Services\Payments\PricingRuleEngine;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function output(string $value): void
{
    fwrite(STDOUT, $value.PHP_EOL);
}

function buildReference(string $prefix, Carbon $timestamp, int $sequence): string
{
    return sprintf(
        '%s-%s-%s-%04d',
        strtoupper($prefix),
        $timestamp->format('ymd'),
        $timestamp->format('Hi'),
        $sequence
    );
}

function buildGatewayReference(Carbon $timestamp, int $sequence): string
{
    return sprintf(
        'PSTK-CI-%s-%04d',
        $timestamp->format('ymdHis'),
        $sequence
    );
}

$tenant = Tenant::query()->where('slug', 'demo-front-buyer')->firstOrFail();
$gateway = PaymentGateway::query()
    ->where('code', 'paystack')
    ->where('is_active', true)
    ->first();

abort_if($gateway === null, 1, 'Paystack gateway not found.');

$pricingEngine = $app->make(PricingRuleEngine::class);
$orderFulfillment = $app->make(OrderFulfillmentService::class);

$sequence = [
    ['code' => 'SUMMIT-STD', 'quantity' => 1, 'method' => 'card'],
    ['code' => 'EVENT-PAID-STD', 'quantity' => 2, 'method' => 'orange_money'],
    ['code' => 'EVENT-FREE-STD', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'SUMMIT-VIP', 'quantity' => 1, 'method' => 'wave'],
    ['code' => 'EVENT-PAID-VIP', 'quantity' => 1, 'method' => 'card'],
    ['code' => 'SUMMIT-STD', 'quantity' => 2, 'method' => 'mtn_money'],
    ['code' => 'EVENT-PAID-STD', 'quantity' => 1, 'method' => 'moov_money'],
    ['code' => 'EVENT-FREE-STD', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'TRAINING-PAID', 'quantity' => 1, 'method' => 'card'],
    ['code' => 'TRAINING-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'TRAINING-PAID', 'quantity' => 2, 'method' => 'wave'],
    ['code' => 'TRAINING-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'TRAINING-PAID', 'quantity' => 1, 'method' => 'orange_money'],
    ['code' => 'TRAINING-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'TRAINING-PAID', 'quantity' => 1, 'method' => 'card'],
    ['code' => 'TRAINING-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'STAND-PAID', 'quantity' => 2, 'method' => 'card'],
    ['code' => 'STAND-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'STAND-PAID', 'quantity' => 1, 'method' => 'mtn_money'],
    ['code' => 'STAND-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'STAND-PAID', 'quantity' => 1, 'method' => 'wave'],
    ['code' => 'STAND-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'STAND-PAID', 'quantity' => 1, 'method' => 'orange_money'],
    ['code' => 'CALL-PAID', 'quantity' => 1, 'method' => 'card'],
    ['code' => 'CALL-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'CALL-PAID', 'quantity' => 1, 'method' => 'wave'],
    ['code' => 'CALL-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'CALL-PAID', 'quantity' => 1, 'method' => 'mtn_money'],
    ['code' => 'CALL-FREE', 'quantity' => 1, 'method' => 'free'],
    ['code' => 'CALL-PAID', 'quantity' => 1, 'method' => 'moov_money'],
];

$summary = [
    'orders' => 0,
    'receipts' => 0,
    'passes' => 0,
    'submissions' => 0,
];

$tenant->run(function () use (
    $tenant,
    $gateway,
    $pricingEngine,
    $orderFulfillment,
    $sequence,
    &$summary
): void {
    /** @var User $user */
    $user = User::query()->firstOrNew(['email' => 'gnakaleroland@gmail.com']);
    $user->forceFill([
        'name' => 'Gnakale Roland',
        'username' => 'gnakaleroland',
        'first_name' => 'Roland',
        'last_name' => 'Gnakale',
        'email' => 'gnakaleroland@gmail.com',
        'password' => 'Admin123!',
        'phone' => '+2250701234567',
        'locale' => 'fr',
        'timezone' => 'Africa/Abidjan',
        'email_verified_at' => now(),
        'is_active' => true,
    ])->save();

    $oldOrderIds = Order::query()
        ->where('buyer_user_id', $user->id)
        ->pluck('id');

    $oldTransactionReferences = Order::query()
        ->where('buyer_user_id', $user->id)
        ->pluck('transaction_reference')
        ->filter()
        ->values();

    if ($oldOrderIds->isNotEmpty()) {
        AccessPass::query()->whereIn('order_id', $oldOrderIds)->delete();
        Receipt::query()->whereIn('order_id', $oldOrderIds)->delete();
        Order::query()->whereIn('id', $oldOrderIds)->delete();
    }

    CallForProjectSubmission::query()
        ->where('applicant_email', $user->email)
        ->delete();

    if ($oldTransactionReferences->isNotEmpty()) {
        PlatformTransaction::query()
            ->whereIn('transaction_reference', $oldTransactionReferences->all())
            ->delete();
    }

    DB::connection('central')
        ->table('platform_transactions')
        ->whereRaw("meta->>'buyer_email' = ?", [$user->email])
        ->delete();

    $offers = Offer::query()
        ->whereIn('code', array_column($sequence, 'code'))
        ->get()
        ->keyBy('code');

    foreach ($sequence as $index => $entry) {
        /** @var Offer $offer */
        $offer = $offers->get($entry['code']);

        if (! $offer instanceof Offer) {
            throw new RuntimeException(sprintf('Offer "%s" not found.', $entry['code']));
        }

        $quantity = (int) $entry['quantity'];
        $paymentMethod = (string) $entry['method'];
        $occurredAt = now()
            ->subDays((count($sequence) - 1) - $index)
            ->setTime(9 + ($index % 8), 7 + ($index % 5) * 9);

        $transactionReference = buildReference('PAY', $occurredAt, $index + 1);
        $orderReference = buildReference('ORD', $occurredAt, $index + 1);
        $receiptReference = buildReference('RCP', $occurredAt, $index + 1);
        $gatewayReference = buildGatewayReference($occurredAt, $index + 1);
        $gatewayTransactionId = 6127081813 + $index;
        $pricing = $pricingEngine->quote($tenant, $offer, $quantity, $gateway, $paymentMethod === 'free' ? null : $paymentMethod);

        PlatformTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'payment_gateway_id' => $gateway->id,
            'transaction_reference' => $transactionReference,
            'gateway_reference' => $gatewayReference,
            'type' => 'public_checkout',
            'direction' => 'credit',
            'status' => 'success',
            'gross_amount' => (int) $pricing['customer_total'],
            'fee_amount' => (int) $pricing['total_fee_amount'],
            'net_amount' => (int) $pricing['organizer_net'],
            'gateway_fee_amount' => (int) $pricing['gateway_fee_amount'],
            'platform_fee_amount' => (int) $pricing['platform_fee_amount'],
            'tax_amount' => (int) $pricing['tax_amount'],
            'payout_fee_amount' => 0,
            'customer_fee_amount' => (int) $pricing['customer_fee_total'],
            'absorbed_fee_amount' => (int) $pricing['absorbed_fee_total'],
            'currency_code' => (string) $pricing['currency'],
            'occurred_at' => $occurredAt,
            'meta' => [
                'buyer_user_id' => $user->id,
                'buyer_name' => $user->name,
                'buyer_email' => $user->email,
                'buyer_phone' => $user->phone,
                'gateway_transaction_id' => $gatewayTransactionId,
                'payment_method' => $paymentMethod,
                'offer_code' => $offer->code,
                'offerable_type' => $offer->offerable_type,
            ],
            'pricing_snapshot' => $pricing,
        ]);

        $order = $orderFulfillment->fulfill($transactionReference, [
            'data' => [
                'id' => $gatewayTransactionId,
                'reference' => $gatewayReference,
                'status' => 'success',
                'amount' => (int) $pricing['customer_total'],
                'fees' => (int) ($pricing['breakdown']['gateway_fee']['total'] ?? 0),
                'currency' => (string) $pricing['currency'],
                'paid_at' => $occurredAt->toIso8601String(),
                'pricing_snapshot' => $pricing,
                'metadata' => [
                    'offer_id' => $offer->id,
                    'offerable_type' => $offer->offerable_type,
                    'quantity' => $quantity,
                    'buyer_user_id' => $user->id,
                    'buyer_name' => $user->name,
                    'buyer_email' => $user->email,
                    'buyer_phone' => $user->phone,
                    'payment_method' => $paymentMethod,
                    'pricing_snapshot' => $pricing,
                ],
            ],
        ]);

        if (! $order instanceof Order) {
            throw new RuntimeException(sprintf('Order fulfillment failed for "%s".', $transactionReference));
        }

        $order->forceFill([
            'reference' => $orderReference,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ])->saveQuietly();

        $order->receipt?->forceFill([
            'reference' => $receiptReference,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
            'issued_at' => $occurredAt,
        ])->saveQuietly();

        $order->accessPasses->each(function (AccessPass $pass) use ($occurredAt, $index): void {
            $pass->forceFill([
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);

            if (($index + 1) % 11 === 0) {
                $pass->status = 'used';
                $pass->used_at = $occurredAt->copy()->addHours(2);
            }

            $pass->saveQuietly();
        });

        PlatformTransaction::query()
            ->where('transaction_reference', $transactionReference)
            ->update([
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
                'meta' => [
                    'buyer_user_id' => $user->id,
                    'buyer_name' => $user->name,
                    'buyer_email' => $user->email,
                    'buyer_phone' => $user->phone,
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'payment_method' => $paymentMethod,
                    'offer_code' => $offer->code,
                    'offerable_type' => $offer->offerable_type,
                    'order_reference' => $order->reference,
                    'receipt_reference' => $order->receipt?->reference,
                    'access_passes_count' => $order->accessPasses()->count(),
                ],
            ]);

        if ($offer->offerable_type === CallForProject::class) {
            CallForProjectSubmission::query()->create([
                'public_id' => (string) Str::uuid(),
                'call_for_project_id' => $offer->offerable_id,
                'status' => 'submitted',
                'applicant_name' => $user->name,
                'applicant_email' => $user->email,
                'phone_country_code' => '+225',
                'phone_number' => '0701234567',
                'country_code' => 'CI',
                'city_name' => 'Abidjan',
                'answers' => [
                    'project_title' => sprintf('Projet demo %02d', $index + 1),
                    'motivation' => 'Soumission de demonstration pour le controle du panel organisateur.',
                ],
                'files' => [],
                'submitted_at' => $occurredAt,
                'meta' => [
                    'source' => 'demo_seed',
                    'linked_order_reference' => $order->reference,
                ],
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
        }
    }

    $confirmedByOffer = Order::query()
        ->where('status', 'confirmed')
        ->selectRaw('offer_id, COALESCE(SUM(quantity), 0) as quantity')
        ->groupBy('offer_id')
        ->pluck('quantity', 'offer_id');

    Offer::query()->get()->each(function (Offer $offer) use ($confirmedByOffer): void {
        $offer->forceFill([
            'quantity_sold' => (int) ($confirmedByOffer[$offer->id] ?? 0),
        ])->saveQuietly();
    });

    $summary['orders'] = Order::query()->where('buyer_user_id', $user->id)->count();
    $summary['receipts'] = Receipt::query()->where('buyer_user_id', $user->id)->count();
    $summary['passes'] = AccessPass::query()->where('holder_user_id', $user->id)->count();
    $summary['submissions'] = CallForProjectSubmission::query()->where('applicant_email', $user->email)->count();
});

output('BUYER_EMAIL=gnakaleroland@gmail.com');
output('BUYER_PASSWORD=Admin123!');
output('BUYER_READY=yes');
output('ORDERS='.$summary['orders']);
output('RECEIPTS='.$summary['receipts']);
output('PASSES='.$summary['passes']);
output('CALL_SUBMISSIONS='.$summary['submissions']);

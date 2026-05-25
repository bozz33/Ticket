<?php

namespace Ticket\Payments\Application;

use App\Enums\AccessPassStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Models\AccessPass;
use App\Models\CrowdfundingContribution;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\Refund;
use App\Notifications\BuyerOrderRefundedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantRefundService
{
    public function apply(Refund $refund, Order $order): void
    {
        $status = $refund->status instanceof RefundStatus ? $refund->status : RefundStatus::from((string) $refund->status);
        $connectionName = config('ticket.tenant_connection', 'tenant');

        DB::connection($connectionName)->transaction(function () use ($refund, $order, $status): void {
            $freshOrder = Order::query()
                ->with(['receipt', 'accessPasses'])
                ->findOrFail($order->getKey());

            match ($status) {
                RefundStatus::Pending,
                RefundStatus::Processing => $this->markRefundPending($refund, $freshOrder),
                RefundStatus::Refunded => $this->markRefunded($refund, $freshOrder),
                RefundStatus::Failed,
                RefundStatus::Rejected => $this->revertPendingRefund($refund, $freshOrder),
            };
        });
    }

    public function assertOrderCanBeRefunded(Order $order): void
    {
        if (! in_array($order->status?->value ?? $order->status, [OrderStatus::Confirmed->value, OrderStatus::RefundPending->value], true)) {
            throw new \RuntimeException('Seules les commandes confirmées peuvent être remboursées.');
        }

        $hasUsedPass = $order->accessPasses()
            ->where(function ($query): void {
                $query->where('status', AccessPassStatus::Used->value)
                    ->orWhereNotNull('used_at');
            })
            ->exists();

        if ($hasUsedPass) {
            throw new \RuntimeException('Impossible de rembourser une commande dont un pass a déjà été utilisé.');
        }
    }

    private function markRefundPending(Refund $refund, Order $order): void
    {
        $this->revokeRefundablePasses($refund, $order);

        $meta = array_merge((array) ($order->meta ?? []), [
            'refund' => [
                'reference' => $refund->reference,
                'status' => $refund->status?->value ?? $refund->status,
                'processed_at' => $refund->processed_at?->toIso8601String(),
            ],
        ]);

        $order->forceFill([
            'status' => OrderStatus::RefundPending,
            'refunded_amount' => (int) $refund->amount_refunded_to_buyer,
            'refund_reference' => $refund->reference,
            'meta' => $meta,
        ])->save();

        $this->updateReceipt($refund, $order->receipt, 'refund_pending');
    }

    private function markRefunded(Refund $refund, Order $order): void
    {
        $this->revokeRefundablePasses($refund, $order);

        $meta = array_merge((array) ($order->meta ?? []), [
            'refund' => [
                'reference' => $refund->reference,
                'status' => RefundStatus::Refunded->value,
                'processed_at' => $refund->processed_at?->toIso8601String(),
            ],
        ]);

        $order->forceFill([
            'status' => OrderStatus::Refunded,
            'refunded_amount' => (int) $refund->amount_refunded_to_buyer,
            'refunded_at' => $refund->processed_at ?? now(),
            'refund_reference' => $refund->reference,
            'meta' => $meta,
        ])->save();

        $this->markCrowdfundingContributionRefunded($refund, $order);

        if ($order->buyer) {
            $order->buyer->notify(new BuyerOrderRefundedNotification($order, $refund));
        }

        $this->updateReceipt($refund, $order->receipt, 'refunded');
    }

    private function revertPendingRefund(Refund $refund, Order $order): void
    {
        if (($order->refund_reference ?? null) !== $refund->reference) {
            return;
        }

        if (($order->status?->value ?? $order->status) !== OrderStatus::RefundPending->value) {
            return;
        }

        $orderMeta = array_merge((array) ($order->meta ?? []), [
            'refund' => [
                'reference' => $refund->reference,
                'status' => $refund->status?->value ?? $refund->status,
                'processed_at' => $refund->processed_at?->toIso8601String(),
            ],
        ]);

        $order->forceFill([
            'status' => OrderStatus::Confirmed,
            'refunded_amount' => 0,
            'refunded_at' => null,
            'refund_reference' => null,
            'meta' => $orderMeta,
        ])->save();

        if ($order->receipt) {
            $receiptMeta = array_merge((array) ($order->receipt->meta ?? []), [
                'refund' => [
                    'reference' => $refund->reference,
                    'status' => $refund->status?->value ?? $refund->status,
                    'processed_at' => $refund->processed_at?->toIso8601String(),
                ],
            ]);

            $order->receipt->forceFill([
                'status' => 'issued',
                'refunded_amount' => 0,
                'refunded_at' => null,
                'meta' => $receiptMeta,
            ])->save();
        }

        $reason = $this->refundRevocationReason($refund);

        $order->accessPasses()
            ->where('status', AccessPassStatus::Revoked->value)
            ->where('revocation_reason', $reason)
            ->each(function (AccessPass $pass): void {
                $pass->forceFill([
                    'status' => AccessPassStatus::Active,
                    'revoked_at' => null,
                    'revocation_reason' => null,
                ])->save();
            });
    }

    private function revokeRefundablePasses(Refund $refund, Order $order): void
    {
        $reason = $this->refundRevocationReason($refund);

        $order->accessPasses()
            ->whereIn('status', [AccessPassStatus::Active->value, AccessPassStatus::Revoked->value])
            ->each(function (AccessPass $pass) use ($reason): void {
                if ($pass->status === AccessPassStatus::Revoked && $pass->revocation_reason !== $reason) {
                    return;
                }

                $pass->forceFill([
                    'status' => AccessPassStatus::Revoked,
                    'revoked_at' => now(),
                    'revocation_reason' => $reason,
                ])->save();
            });
    }

    private function markCrowdfundingContributionRefunded(Refund $refund, Order $order): void
    {
        if (! Schema::connection(config('ticket.tenant_connection', 'tenant'))->hasTable('crowdfunding_contributions')) {
            return;
        }

        $contribution = CrowdfundingContribution::query()
            ->with(['campaign', 'offer'])
            ->where('order_id', $order->getKey())
            ->first();

        if (! $contribution instanceof CrowdfundingContribution || $contribution->status === 'refunded') {
            return;
        }

        $refundAmount = min(max(0, (int) $refund->amount_refunded_to_buyer), (int) $contribution->amount);

        $contribution->forceFill([
            'status' => 'refunded',
            'refunded_amount' => $refundAmount,
            'refunded_at' => $refund->processed_at ?? now(),
            'meta' => array_merge((array) ($contribution->meta ?? []), [
                'refund_reference' => $refund->reference,
            ]),
        ])->save();

        if ($contribution->campaign) {
            $contribution->campaign->forceFill([
                'raised_amount' => max(0, (int) $contribution->campaign->raised_amount - $refundAmount),
            ])->save();
        }

        if ($contribution->offer) {
            $quantity = max(1, (int) (($contribution->meta ?? [])['quantity'] ?? 1));

            $contribution->offer->forceFill([
                'quantity_sold' => max(0, (int) $contribution->offer->quantity_sold - $quantity),
            ])->save();
        }
    }

    private function updateReceipt(Refund $refund, ?Receipt $receipt, string $status): void
    {
        if (! $receipt) {
            return;
        }

        $meta = array_merge((array) ($receipt->meta ?? []), [
            'refund' => [
                'reference' => $refund->reference,
                'status' => $refund->status?->value ?? $refund->status,
                'processed_at' => $refund->processed_at?->toIso8601String(),
            ],
        ]);

        $receipt->forceFill([
            'status' => $status,
            'refunded_amount' => (int) $refund->amount_refunded_to_buyer,
            'refunded_at' => $status === 'refunded' ? ($refund->processed_at ?? now()) : null,
            'meta' => $meta,
        ])->save();
    }

    private function refundRevocationReason(Refund $refund): string
    {
        return sprintf('refund:%s', $refund->reference);
    }
}

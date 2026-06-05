<?php

namespace Ticket\AccessControl\Application;

use App\Enums\AccessPassStatus;
use App\Enums\ScanResult;
use App\Models\AccessPass;
use App\Models\AccessPassScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ticket\AccessControl\Contracts\AccessPassCheckinWorkflow;

class AccessPassCheckinService implements AccessPassCheckinWorkflow
{
    /**
     * Preview a pass without consuming it.
     * Returns pass info and validation state without side effects.
     */
    public function preview(AccessPass $pass, Request $request): array
    {
        $result = $this->resolveReadResult($pass);

        $this->recordScan($pass, 'preview', $result, $request);

        return $this->buildResponse($pass, $result);
    }

    /**
     * Consume a pass (mark as used) with a row-level lock to prevent double-validation
     * under concurrent scan requests from multiple terminals.
     *
     * The pass is re-fetched inside the transaction with SELECT FOR UPDATE so that
     * two concurrent requests reading the same active pass cannot both succeed.
     */
    public function consume(AccessPass $pass, Request $request): array
    {
        $connectionName = config('ticket.tenant_connection', 'tenant');

        return DB::connection($connectionName)->transaction(function () use ($pass, $request, $connectionName): array {
            $locked = AccessPass::on($connectionName)
                ->whereKey($pass->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isConsumable()) {
                $result = $this->resolveReadResult($locked);
                $this->recordScan($locked, 'consume', $result, $request);

                return $this->buildResponse($locked, $result);
            }

            $locked->forceFill([
                'status' => AccessPassStatus::Used,
                'used_at' => now(),
            ])->save();

            $this->recordScan($locked, 'consume', ScanResult::Granted, $request);

            return $this->buildResponse($locked->fresh(), ScanResult::Granted);
        });
    }

    /**
     * Reset a used pass back to active.
     */
    public function reset(AccessPass $pass, Request $request): array
    {
        if ($pass->status !== AccessPassStatus::Used) {
            return $this->buildResponse($pass, ScanResult::Denied, 'Le pass n\'est pas dans l\'état "utilisé".');
        }

        $connectionName = config('ticket.tenant_connection', 'tenant');

        DB::connection($connectionName)->transaction(function () use ($pass, $request): void {
            $pass->forceFill([
                'status' => AccessPassStatus::Active,
                'used_at' => null,
            ])->save();

            $this->recordScan($pass, 'reset', ScanResult::Granted, $request);
        });

        return $this->buildResponse($pass->fresh(), ScanResult::Granted);
    }

    /**
     * Revoke a pass permanently.
     */
    public function revoke(AccessPass $pass, Request $request, string $reason = ''): array
    {
        if ($pass->status === AccessPassStatus::Revoked) {
            return $this->buildResponse($pass, ScanResult::Denied, 'Le pass est déjà révoqué.');
        }

        $connectionName = config('ticket.tenant_connection', 'tenant');

        DB::connection($connectionName)->transaction(function () use ($pass, $request, $reason): void {
            $pass->forceFill([
                'status' => AccessPassStatus::Revoked,
                'revoked_at' => now(),
                'revocation_reason' => $reason ?: null,
            ])->save();

            $this->recordScan($pass, 'revoke', ScanResult::Granted, $request, ['reason' => $reason]);
        });

        return $this->buildResponse($pass->fresh(), ScanResult::Granted);
    }

    /**
     * Reactivate a revoked pass.
     */
    public function reactivate(AccessPass $pass, Request $request): array
    {
        if ($pass->status !== AccessPassStatus::Revoked) {
            return $this->buildResponse($pass, ScanResult::Denied, 'Seul un pass révoqué peut être réactivé.');
        }

        $connectionName = config('ticket.tenant_connection', 'tenant');

        DB::connection($connectionName)->transaction(function () use ($pass, $request): void {
            $pass->forceFill([
                'status' => AccessPassStatus::Active,
                'revoked_at' => null,
                'revocation_reason' => null,
            ])->save();

            $this->recordScan($pass, 'reactivate', ScanResult::Granted, $request);
        });

        return $this->buildResponse($pass->fresh(), ScanResult::Granted);
    }

    protected function resolveReadResult(AccessPass $pass): ScanResult
    {
        return match ($pass->status) {
            AccessPassStatus::Active => ($pass->expires_at !== null && $pass->expires_at->isPast())
                ? ScanResult::Expired
                : ScanResult::Granted,
            AccessPassStatus::Used => ScanResult::AlreadyUsed,
            AccessPassStatus::Revoked => ScanResult::Revoked,
            AccessPassStatus::Expired => ScanResult::Expired,
        };
    }

    protected function recordScan(
        AccessPass $pass,
        string $action,
        ScanResult $result,
        Request $request,
        array $extra = [],
    ): AccessPassScan {
        return AccessPassScan::query()->create([
            'access_pass_id' => $pass->id,
            'scanned_by' => $request->attributes->get('tenant_user')?->id,
            'action' => $action,
            'result' => $result,
            'terminal_id' => $request->header('X-Terminal-Id'),
            'ip_address' => $request->ip(),
            'scanned_at' => now(),
            'meta' => $extra ?: null,
        ]);
    }

    protected function buildResponse(AccessPass $pass, ScanResult $result, string $message = ''): array
    {
        return [
            'result' => $result->value,
            'result_label' => ScanResult::options()[$result->value],
            'access_granted' => $result->isSuccess(),
            'message' => $message ?: ScanResult::options()[$result->value],
            'pass' => [
                'public_id' => $pass->public_id,
                'access_code' => $pass->access_code,
                'type' => $pass->type->value,
                'type_label' => $pass->type->label(),
                'status' => $pass->status->value,
                'holder_name' => $pass->holder_name,
                'holder_email' => $pass->holder_email,
                'used_at' => $pass->used_at?->toIso8601String(),
                'expires_at' => $pass->expires_at?->toIso8601String(),
                'revoked_at' => $pass->revoked_at?->toIso8601String(),
            ],
        ];
    }
}

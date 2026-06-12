<?php

namespace Ticket\Payments\Application;

use Illuminate\Support\Facades\DB;

/**
 * Allocates a gapless, per-year sequential receipt number scoped to the tenant database
 * (e.g. RCP-2026-000001). Each tenant has its own database, so the sequence is naturally
 * per tenant; the year column resets the counter each calendar year for accounting.
 *
 * Must run inside a database transaction: it locks the per-year sequence row so concurrent
 * order fulfillments cannot mint duplicate numbers. Fulfillment already wraps order, receipt
 * and pass creation in a single transaction, so this is called from there.
 */
class ReceiptNumberAllocator
{
    public function allocate(string $connection, int $year): string
    {
        $connection = DB::connection($connection);

        // Create the year row if it does not exist yet; the unique constraint on `year`
        // makes this safe under concurrency (a racing insert is ignored).
        $connection->table('receipt_number_sequences')->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = $connection->table('receipt_number_sequences')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $next = (int) ($row->last_number ?? 0) + 1;

        $connection->table('receipt_number_sequences')
            ->where('year', $year)
            ->update([
                'last_number' => $next,
                'updated_at' => now(),
            ]);

        return sprintf('RCP-%d-%06d', $year, $next);
    }
}

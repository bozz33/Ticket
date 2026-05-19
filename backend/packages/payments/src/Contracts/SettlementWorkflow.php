<?php

namespace Ticket\Payments\Contracts;

use App\Models\PlatformUser;
use App\Models\Settlement;

interface SettlementWorkflow
{
    public function notifyPlatformOfRequest(Settlement $settlement): void;

    public function approve(Settlement $settlement, PlatformUser $actor): Settlement;

    public function reject(Settlement $settlement, PlatformUser $actor, string $reason): Settlement;

    public function reviewSummary(Settlement $settlement): string;

    public function pendingCount(): int;

    public function makePlatformDatabaseNotification(Settlement $settlement): array;
}

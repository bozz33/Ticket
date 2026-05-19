<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\PlatformUser;
use App\Models\Settlement;
use Ticket\Payments\Application\SettlementWorkflowService;
use Ticket\Payments\Contracts\SettlementWorkflow;

class LaravelSettlementWorkflow implements SettlementWorkflow
{
    public function __construct(
        private readonly SettlementWorkflowService $settlements,
    ) {}

    public function notifyPlatformOfRequest(Settlement $settlement): void
    {
        $this->settlements->notifyPlatformOfRequest($settlement);
    }

    public function approve(Settlement $settlement, PlatformUser $actor): Settlement
    {
        return $this->settlements->approve($settlement, $actor);
    }

    public function reject(Settlement $settlement, PlatformUser $actor, string $reason): Settlement
    {
        return $this->settlements->reject($settlement, $actor, $reason);
    }

    public function reviewSummary(Settlement $settlement): string
    {
        return $this->settlements->reviewSummary($settlement);
    }

    public function pendingCount(): int
    {
        return $this->settlements->pendingCount();
    }

    public function makePlatformDatabaseNotification(Settlement $settlement): array
    {
        return $this->settlements->makePlatformDatabaseNotification($settlement);
    }
}

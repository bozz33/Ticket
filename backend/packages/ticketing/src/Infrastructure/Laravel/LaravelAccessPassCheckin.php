<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\AccessPass;
use App\Support\Microservices\DomainEventBridge;
use Illuminate\Http\Request;
use Ticket\AccessControl\Contracts\AccessPassCheckinWorkflow;
use Ticket\Notifications\Domain\DomainEventNames;
use Ticket\Ticketing\Contracts\AccessPassCheckin;

class LaravelAccessPassCheckin implements AccessPassCheckin
{
    public function __construct(
        private readonly AccessPassCheckinWorkflow $checkin,
        private readonly DomainEventBridge $events,
    ) {}

    public function preview(AccessPass $pass, Request $request): array
    {
        return $this->checkin->preview($pass, $request);
    }

    public function consume(AccessPass $pass, Request $request): array
    {
        $result = $this->checkin->consume($pass, $request);

        if (($result['access_granted'] ?? false) === true) {
            $this->events->publish(
                DomainEventNames::ACCESS_PASS_CHECKED_IN,
                [
                    'access_pass_id' => $pass->getKey(),
                    'access_code' => $pass->access_code,
                    'holder_email' => $pass->holder_email,
                    'holder_name' => $pass->holder_name,
                    'status' => $result['status'] ?? null,
                    'checked_in_at' => now()->toISOString(),
                    'result' => $result,
                ],
                AccessPass::class,
                (string) $pass->getKey(),
                ['module' => 'ticketing', 'tenant_id' => tenant('id')],
            );
        }

        return $result;
    }

    public function reset(AccessPass $pass, Request $request): array
    {
        return $this->checkin->reset($pass, $request);
    }

    public function revoke(AccessPass $pass, Request $request, string $reason = ''): array
    {
        return $this->checkin->revoke($pass, $request, $reason);
    }

    public function reactivate(AccessPass $pass, Request $request): array
    {
        return $this->checkin->reactivate($pass, $request);
    }
}

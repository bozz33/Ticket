<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\AccessPass;
use Illuminate\Http\Request;
use Ticket\Ticketing\Application\AccessPassCheckinService;
use Ticket\Ticketing\Contracts\AccessPassCheckin;

class LaravelAccessPassCheckin implements AccessPassCheckin
{
    public function __construct(private readonly AccessPassCheckinService $checkin) {}

    public function preview(AccessPass $pass, Request $request): array
    {
        return $this->checkin->preview($pass, $request);
    }

    public function consume(AccessPass $pass, Request $request): array
    {
        return $this->checkin->consume($pass, $request);
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

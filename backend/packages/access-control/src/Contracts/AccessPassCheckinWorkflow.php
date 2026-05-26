<?php

namespace Ticket\AccessControl\Contracts;

use App\Models\AccessPass;
use Illuminate\Http\Request;

interface AccessPassCheckinWorkflow
{
    public function preview(AccessPass $pass, Request $request): array;

    public function consume(AccessPass $pass, Request $request): array;

    public function reset(AccessPass $pass, Request $request): array;

    public function revoke(AccessPass $pass, Request $request, string $reason = ''): array;

    public function reactivate(AccessPass $pass, Request $request): array;
}

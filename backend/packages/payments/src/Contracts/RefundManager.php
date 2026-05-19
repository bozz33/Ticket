<?php

namespace Ticket\Payments\Contracts;

use App\Models\PlatformTransaction;
use App\Models\PlatformUser;
use App\Models\Refund;

interface RefundManager
{
    public function quote(PlatformTransaction $transaction, ?string $reasonCode = null): array;

    public function create(PlatformTransaction $transaction, array $payload = [], ?PlatformUser $actor = null): Refund;

    public function sync(Refund $refund): Refund;
}

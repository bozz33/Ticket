<?php

namespace Ticket\SupportObservability\Tests\Unit;

use Tests\TestCase;
use Ticket\SupportObservability\Contracts\AuditLogger;

class SupportObservabilityBindingsTest extends TestCase
{
    public function test_audit_logger_contract_is_bound(): void
    {
        $this->assertInstanceOf(AuditLogger::class, app(AuditLogger::class));
    }
}

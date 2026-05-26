<?php

namespace Ticket\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ticket\Notifications\Domain\DomainEventNames;

class DomainEventNamesTest extends TestCase
{
    public function test_domain_event_names_are_unique(): void
    {
        $events = DomainEventNames::all();

        $this->assertSame($events, array_unique($events));
    }

    public function test_domain_event_names_are_dot_notation_contracts(): void
    {
        foreach (DomainEventNames::all() as $eventName) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $eventName);
        }
    }
}

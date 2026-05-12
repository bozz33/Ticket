<?php

namespace Tests\Unit;

use Livewire\Livewire;
use Tests\TestCase;

class LivewireRouteConfigurationTest extends TestCase
{
    public function test_livewire_uses_a_stable_update_uri(): void
    {
        $this->assertSame('/livewire/update', Livewire::getUpdateUri());
    }
}

<?php

namespace Ticket\ContentTraining\Tests\Unit;

use Tests\TestCase;
use Ticket\ContentTraining\Contracts\TrainingContentCatalog;

class ContentTrainingBindingsTest extends TestCase
{
    public function test_training_content_catalog_contract_is_bound(): void
    {
        $this->assertInstanceOf(TrainingContentCatalog::class, app(TrainingContentCatalog::class));
    }
}

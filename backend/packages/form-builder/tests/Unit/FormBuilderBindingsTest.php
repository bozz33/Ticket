<?php

namespace Ticket\FormBuilder\Tests\Unit;

use Tests\TestCase;
use Ticket\FormBuilder\Contracts\FormSchemaValidator;
use Ticket\FormBuilder\Contracts\FormSubmissionWriter;

class FormBuilderBindingsTest extends TestCase
{
    public function test_form_builder_contracts_are_bound(): void
    {
        $this->assertInstanceOf(FormSchemaValidator::class, app(FormSchemaValidator::class));
        $this->assertInstanceOf(FormSubmissionWriter::class, app(FormSubmissionWriter::class));
    }
}

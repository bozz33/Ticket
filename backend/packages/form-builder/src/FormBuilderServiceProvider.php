<?php

namespace Ticket\FormBuilder;

use Illuminate\Support\ServiceProvider;
use Ticket\FormBuilder\Application\DynamicFormValidator;
use Ticket\FormBuilder\Application\PublicFormSubmissionService;
use Ticket\FormBuilder\Contracts\FormSchemaValidator;
use Ticket\FormBuilder\Contracts\FormSubmissionWriter;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FormSchemaValidator::class, DynamicFormValidator::class);
        $this->app->bind(FormSubmissionWriter::class, PublicFormSubmissionService::class);
    }
}

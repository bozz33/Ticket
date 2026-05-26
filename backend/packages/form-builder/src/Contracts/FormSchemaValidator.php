<?php

namespace Ticket\FormBuilder\Contracts;

interface FormSchemaValidator
{
    public function validateSchema(array $schema): array;

    public function validateSubmission(array $schema, array $data): array;
}

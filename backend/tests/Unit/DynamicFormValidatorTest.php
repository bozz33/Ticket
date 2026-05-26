<?php

namespace Tests\Unit;

use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Ticket\FormBuilder\Application\DynamicFormValidator;

class DynamicFormValidatorTest extends TestCase
{
    public function test_it_accepts_a_valid_schema(): void
    {
        $schema = [
            'title' => 'Candidature',
            'fields' => [
                ['key' => 'full_name', 'type' => 'text', 'label' => 'Nom complet', 'required' => true],
                ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ['key' => 'category', 'type' => 'select', 'label' => 'Catégorie', 'options' => [['value' => 'music', 'label' => 'Musique']]],
            ],
        ];

        $validated = $this->validator()->validateSchema($schema);

        $this->assertSame('Candidature', $validated['title']);
        $this->assertCount(3, $validated['fields']);
    }

    public function test_it_rejects_duplicate_field_keys(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator()->validateSchema([
            'fields' => [
                ['key' => 'email', 'type' => 'email', 'label' => 'Email'],
                ['key' => 'email', 'type' => 'text', 'label' => 'Autre email'],
            ],
        ]);
    }

    public function test_it_rejects_unsupported_field_types(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator()->validateSchema([
            'fields' => [
                ['key' => 'custom', 'type' => 'unknown', 'label' => 'Custom'],
            ],
        ]);
    }

    public function test_it_validates_submission_data(): void
    {
        $schema = [
            'fields' => [
                ['key' => 'full_name', 'type' => 'text', 'label' => 'Nom complet', 'required' => true],
                ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ['key' => 'category', 'type' => 'select', 'label' => 'Catégorie', 'required' => true, 'options' => [['value' => 'music', 'label' => 'Musique']]],
            ],
        ];

        $validated = $this->validator()->validateSubmission($schema, [
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'category' => 'music',
        ]);

        $this->assertSame('Ada Lovelace', $validated['full_name']);
        $this->assertSame('ada@example.com', $validated['email']);
        $this->assertSame('music', $validated['category']);
    }

    public function test_it_rejects_invalid_select_submission_value(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator()->validateSubmission([
            'fields' => [
                ['key' => 'category', 'type' => 'select', 'label' => 'Catégorie', 'required' => true, 'options' => [['value' => 'music', 'label' => 'Musique']]],
            ],
        ], [
            'category' => 'unknown',
        ]);
    }

    private function validator(): DynamicFormValidator
    {
        return new DynamicFormValidator;
    }
}

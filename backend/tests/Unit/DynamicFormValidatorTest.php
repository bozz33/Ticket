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

    public function test_it_accepts_new_field_types_in_schema(): void
    {
        $schema = [
            'fields' => [
                ['key' => 'arrival', 'type' => 'time', 'label' => 'Heure d\'arrivée'],
                ['key' => 'meeting', 'type' => 'datetime', 'label' => 'Date et heure'],
                ['key' => 'stay', 'type' => 'date_range', 'label' => 'Période'],
                ['key' => 'score', 'type' => 'rating', 'label' => 'Note'],
                ['key' => 'ref', 'type' => 'hidden', 'label' => 'Référence'],
            ],
        ];

        $validated = $this->validator()->validateSchema($schema);

        $this->assertCount(5, $validated['fields']);
    }

    public function test_time_field_validates_hh_mm_format(): void
    {
        $schema = ['fields' => [['key' => 'start', 'type' => 'time', 'label' => 'Début', 'required' => true]]];

        $result = $this->validator()->validateSubmission($schema, ['start' => '09:30']);
        $this->assertSame('09:30', $result['start']);
    }

    public function test_time_field_rejects_invalid_format(): void
    {
        $this->expectException(ValidationException::class);

        $schema = ['fields' => [['key' => 'start', 'type' => 'time', 'label' => 'Début', 'required' => true]]];
        $this->validator()->validateSubmission($schema, ['start' => 'not-a-time']);
    }

    public function test_rating_field_is_bounded_by_max_rating(): void
    {
        $schema = ['fields' => [['key' => 'score', 'type' => 'rating', 'label' => 'Note', 'required' => true, 'max_rating' => 3]]];

        $result = $this->validator()->validateSubmission($schema, ['score' => 3]);
        $this->assertSame(3, $result['score']);
    }

    public function test_rating_field_rejects_value_above_max_rating(): void
    {
        $this->expectException(ValidationException::class);

        $schema = ['fields' => [['key' => 'score', 'type' => 'rating', 'label' => 'Note', 'required' => true, 'max_rating' => 3]]];
        $this->validator()->validateSubmission($schema, ['score' => 4]);
    }

    public function test_date_range_field_accepts_array_value(): void
    {
        $schema = ['fields' => [['key' => 'stay', 'type' => 'date_range', 'label' => 'Période', 'required' => true]]];

        $result = $this->validator()->validateSubmission($schema, ['stay' => ['start' => '2026-06-01', 'end' => '2026-06-10']]);
        $this->assertIsArray($result['stay']);
    }

    public function test_hidden_field_accepts_string_value(): void
    {
        $schema = ['fields' => [['key' => 'ref', 'type' => 'hidden', 'label' => 'Ref']]];

        $result = $this->validator()->validateSubmission($schema, ['ref' => 'ORDER-123']);
        $this->assertSame('ORDER-123', $result['ref']);
    }

    private function validator(): DynamicFormValidator
    {
        return new DynamicFormValidator;
    }
}

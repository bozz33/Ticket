<?php

namespace Tests\Unit;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Ticket\FormBuilder\Application\DynamicFormValidator;
use Ticket\FormBuilder\Application\PublicFormSubmissionService;

class PublicFormSubmissionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Runs against the dedicated PostgreSQL testing database (phpunit.xml) so type
        // strictness matches production.
        DB::purge('tenant');

        $this->prepareTenantSchema();
    }

    public function test_it_stores_valid_public_form_submission(): void
    {
        $form = FormDefinition::query()->create([
            'name' => 'application',
            'title' => 'Candidature',
            'status' => 'published',
            'schema' => [
                'fields' => [
                    ['key' => 'full_name', 'type' => 'text', 'label' => 'Nom complet', 'required' => true],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ],
            ],
        ]);

        $request = Request::create('/forms', 'POST', [
            'responses' => [
                'full_name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
            ],
        ]);

        $submission = $this->service()->submit($form, $request);

        $this->assertInstanceOf(FormSubmission::class, $submission);
        $this->assertSame('submitted', $submission->status);
        $this->assertSame('Ada Lovelace', $submission->data['full_name']);
        $this->assertSame('ada@example.com', $submission->data['email']);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_it_rejects_unpublished_form_submission(): void
    {
        $this->expectException(ValidationException::class);

        $form = FormDefinition::query()->create([
            'name' => 'draft',
            'title' => 'Brouillon',
            'status' => 'draft',
            'schema' => ['fields' => []],
        ]);

        $this->service()->submit($form, Request::create('/forms', 'POST', ['responses' => []]));
    }

    public function test_it_stores_uploaded_files_for_file_fields(): void
    {
        Storage::fake('local');

        $form = FormDefinition::query()->create([
            'name' => 'with-file',
            'title' => 'Avec fichier',
            'status' => 'published',
            'schema' => [
                'fields' => [
                    ['key' => 'full_name', 'type' => 'text', 'label' => 'Nom complet', 'required' => true],
                    ['key' => 'portfolio', 'type' => 'file', 'label' => 'Portfolio', 'required' => true],
                ],
            ],
        ]);

        $request = Request::create('/forms', 'POST', [
            'responses' => ['full_name' => 'Grace Hopper'],
        ], [], [
            'files' => ['portfolio' => UploadedFile::fake()->create('portfolio.pdf', 128, 'application/pdf')],
        ]);

        $submission = $this->service()->submit($form, $request);

        $this->assertSame('Grace Hopper', $submission->data['full_name']);
        $this->assertArrayHasKey('portfolio', $submission->files);
        $this->assertSame('portfolio.pdf', $submission->files['portfolio']['original_name']);
        Storage::disk('local')->assertExists($submission->files['portfolio']['path']);
    }

    private function service(): PublicFormSubmissionService
    {
        return new PublicFormSubmissionService(new DynamicFormValidator);
    }

    private function prepareTenantSchema(): void
    {
        Schema::connection('tenant')->dropAllTables();

        Schema::connection('tenant')->create('form_definitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->nullableMorphs('owner');
            $table->string('name');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('submit_label')->default('Envoyer');
            $table->longText('success_message')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->json('schema')->nullable();
            $table->json('validation_schema')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('form_definition_id')->constrained('form_definitions')->cascadeOnDelete();
            $table->unsignedBigInteger('submitter_user_id')->nullable();
            $table->string('status', 40)->default('submitted')->index();
            $table->json('data')->nullable();
            $table->json('files')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
}

<?php

namespace Tests\Unit;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Services\Forms\DynamicFormValidator;
use App\Services\Forms\PublicFormSubmissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublicFormSubmissionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.tenant.driver', 'sqlite');
        config()->set('database.connections.tenant.database', ':memory:');
        config()->set('database.connections.tenant.foreign_key_constraints', true);

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

    private function service(): PublicFormSubmissionService
    {
        return new PublicFormSubmissionService(new DynamicFormValidator());
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

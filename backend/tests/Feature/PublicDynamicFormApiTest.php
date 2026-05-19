<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicDynamicFormApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.tenant.driver', 'sqlite');
        config()->set('database.connections.tenant.database', ':memory:');
        config()->set('database.connections.tenant.foreign_key_constraints', true);

        DB::purge('tenant');

        $this->prepareTenantSchema();
        $this->withoutMiddleware();
    }

    public function test_public_form_can_be_displayed_and_submitted_by_public_id(): void
    {
        $form = FormDefinition::query()->create([
            'public_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'public-application',
            'title' => 'Candidature dynamique',
            'status' => 'published',
            'schema' => [
                'fields' => [
                    ['key' => 'full_name', 'type' => 'text', 'label' => 'Nom complet', 'required' => true],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ],
            ],
        ]);

        $this->getJson("/api/v1/public/tenants/demo/forms/{$form->public_id}")
            ->assertOk()
            ->assertJsonPath('data.id', $form->public_id)
            ->assertJsonPath('data.title', 'Candidature dynamique');

        $this->postJson("/api/v1/public/tenants/demo/forms/{$form->public_id}/submissions", [
            'responses' => [
                'full_name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('form_submissions', [
            'form_definition_id' => $form->getKey(),
            'status' => 'submitted',
        ], 'tenant');
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('translation_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('group', 120)->default('front')->index();
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['language_id', 'key']);
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('translation_entries');
    }

    private function seedDefaults(): void
    {
        if (! Schema::connection('central')->hasTable('languages')) {
            return;
        }

        $connection = DB::connection('central');
        $now = now();

        foreach ($this->languages() as $language) {
            $existing = $connection->table('languages')->where('code', $language['code'])->first();

            $connection->table('languages')->updateOrInsert(
                ['code' => $language['code']],
                [
                    'locale' => $language['locale'],
                    'name' => $language['name'],
                    'native_name' => $language['native_name'],
                    'sort_order' => $language['sort_order'],
                    'is_active' => true,
                    'meta' => json_encode($language['meta'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );
        }

        $languages = $connection->table('languages')->whereIn('code', ['fr', 'en'])->pluck('id', 'code');

        foreach ($this->translations() as $locale => $entries) {
            $languageId = $languages[$locale] ?? null;

            if (! $languageId) {
                continue;
            }

            foreach ($entries as $key => $value) {
                $existing = $connection->table('translation_entries')
                    ->where('language_id', $languageId)
                    ->where('key', $key)
                    ->first();

                $connection->table('translation_entries')->updateOrInsert(
                    ['language_id' => $languageId, 'key' => $key],
                    [
                        'group' => str_contains($key, '.') ? strtok($key, '.') : 'front',
                        'value' => $value,
                        'is_active' => true,
                        'meta' => null,
                        'created_at' => $existing?->created_at ?? $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }

    private function languages(): array
    {
        return [
            [
                'code' => 'fr',
                'locale' => 'fr',
                'name' => 'Français',
                'native_name' => 'Français',
                'sort_order' => 1,
                'meta' => ['is_default' => true],
            ],
            [
                'code' => 'en',
                'locale' => 'en',
                'name' => 'Anglais',
                'native_name' => 'English',
                'sort_order' => 2,
                'meta' => ['is_default' => false],
            ],
        ];
    }

    private function translations(): array
    {
        return [
            'fr' => [
                'nav.account' => 'Mon compte',
                'nav.organizer' => 'Devenir organisateur',
                'nav.verify_ticket' => 'Vérifier un ticket',
                'nav.home' => 'Accueil',
                'nav.about' => 'À propos',
                'nav.events' => 'Événements',
                'nav.contact' => 'Contact',
                'header.available' => 'Disponible 24h/24',
                'header.secure_payment' => 'Paiement sécurisé',
                'footer.rights' => 'Tous droits réservés.',
            ],
            'en' => [
                'nav.account' => 'My account',
                'nav.organizer' => 'Become an organizer',
                'nav.verify_ticket' => 'Verify a ticket',
                'nav.home' => 'Home',
                'nav.about' => 'About',
                'nav.events' => 'Events',
                'nav.contact' => 'Contact',
                'header.available' => 'Available 24/7',
                'header.secure_payment' => 'Secure payment',
                'footer.rights' => 'All rights reserved.',
            ],
        ];
    }
};

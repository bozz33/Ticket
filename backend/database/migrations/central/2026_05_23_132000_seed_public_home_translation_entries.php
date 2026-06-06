<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('translation_entries')
            || ! Schema::connection('central')->hasTable('languages')) {
            return;
        }

        $connection = DB::connection('central');
        $languages = $connection->table('languages')->whereIn('code', ['fr', 'en'])->pluck('id', 'code');
        $now = now();

        foreach ($this->entries() as $locale => $entries) {
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

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('translation_entries')
            || ! Schema::connection('central')->hasTable('languages')) {
            return;
        }

        $connection = DB::connection('central');
        $languageIds = $connection->table('languages')->whereIn('code', ['fr', 'en'])->pluck('id');

        $connection->table('translation_entries')
            ->whereIn('language_id', $languageIds)
            ->whereIn('key', array_keys($this->entries()['fr']))
            ->delete();
    }

    private function entries(): array
    {
        return [
            'fr' => [
                'footer.explore' => 'Explorer',
                'footer.platform' => 'Plateforme',
                'footer.payment' => 'Paiement',
                'home.featured.description' => 'Une vitrine riche et visuelle, avec des cartes denses et des CTA directs.',
                'home.featured.eyebrow' => 'Sélection éditée',
                'home.featured.title' => 'À la une',
                'home.hero.body' => "Un catalogue premium pour billets, formations, stands, candidatures et campagnes, avec des parcours d'achat clairs et une mise en avant forte des organisateurs.",
                'home.hero.eyebrow' => 'Marketplace publique',
                'home.hero.image_alt' => 'Scène premium et public pendant un événement',
                'home.hero.primary_cta' => 'Vérifier un ticket',
                'home.hero.secondary_cta' => 'Publier sur la plateforme',
                'home.hero.title' => 'Des expériences à réserver, soutenir ou rejoindre.',
                'home.organizers.description' => 'Chaque organisateur peut être valorisé comme une vraie page publique.',
                'home.organizers.eyebrow' => 'Organisateurs',
                'home.organizers.title' => 'Profils publics mis en avant',
                'home.popular.description' => "Les contenus qui ont reçu le plus de mentions j'aime cette semaine.",
                'home.popular.eyebrow' => 'Tendances',
                'home.popular.title' => 'Populaires cette semaine',
                'home.stats.items' => 'Contenus publiés',
                'home.stats.organizers' => 'Organisateurs',
                'home.stats.users' => 'Utilisateurs',
                'nav.categories' => 'Catégories',
                'nav.faq' => 'FAQ',
                'nav.legal' => 'Mentions légales',
                'nav.refunds' => 'CGV & remboursements',
            ],
            'en' => [
                'footer.explore' => 'Explore',
                'footer.platform' => 'Platform',
                'footer.payment' => 'Payment',
                'home.featured.description' => 'A rich visual showcase with dense cards and direct calls to action.',
                'home.featured.eyebrow' => "Editor's picks",
                'home.featured.title' => 'Featured',
                'home.hero.body' => 'A premium catalogue for tickets, training, stands, applications and campaigns, with clear purchase paths and strong organizer visibility.',
                'home.hero.eyebrow' => 'Public marketplace',
                'home.hero.image_alt' => 'Premium crowd scene during an event',
                'home.hero.primary_cta' => 'Verify a ticket',
                'home.hero.secondary_cta' => 'Publish on the platform',
                'home.hero.title' => 'Book, support or join memorable experiences.',
                'home.organizers.description' => 'Each organizer can be showcased as a real public profile.',
                'home.organizers.eyebrow' => 'Organizers',
                'home.organizers.title' => 'Featured public profiles',
                'home.popular.description' => 'The content with the most likes this week.',
                'home.popular.eyebrow' => 'Trending',
                'home.popular.title' => 'Popular this week',
                'home.stats.items' => 'Published content',
                'home.stats.organizers' => 'Organizers',
                'home.stats.users' => 'Users',
                'nav.categories' => 'Categories',
                'nav.faq' => 'FAQ',
                'nav.legal' => 'Legal notice',
                'nav.refunds' => 'Terms & refunds',
            ],
        ];
    }
};

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

        $this->replaceLegacyPublicTenantCopy($connection, $now);

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

    private function replaceLegacyPublicTenantCopy(\Illuminate\Database\ConnectionInterface $connection, \Illuminate\Support\Carbon $now): void
    {
        if (Schema::connection('central')->hasTable('front_pages')) {
            $connection->table('front_pages')
                ->where('seo_description', 'Publiez et vendez sur la plateforme Ticket avec un backoffice tenant et un front public unifié.')
                ->update([
                    'seo_description' => 'Publiez et vendez sur la plateforme Ticket avec un espace organisateur et un front public unifié.',
                    'updated_at' => $now,
                ]);
        }

        if (! Schema::connection('central')->hasTable('front_page_sections')) {
            return;
        }

        $sectionReplacements = [
            ['eyebrow', 'Onboarding tenant', 'Onboarding organisateur'],
            ['body', 'Un front public unifié, un backoffice tenant autonome et des parcours de conversion cohérents sur tous les modules.', 'Un front public unifié, un espace organisateur autonome et des parcours de conversion cohérents sur tous les modules.'],
            ['body', 'Chaque tenant peut être valorisé comme une vraie page publique d’organisateur.', 'Chaque organisateur peut être valorisé comme une vraie page publique.'],
        ];

        foreach ($sectionReplacements as [$column, $oldValue, $newValue]) {
            $connection->table('front_page_sections')
                ->where($column, $oldValue)
                ->update([
                    $column => $newValue,
                    'updated_at' => $now,
                ]);
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
                'common.view_all' => 'Tout voir',
                'content_card.date' => 'Date',
                'content_card.follow_organizer' => "Suivre l'orga",
                'content_card.free' => 'Gratuit',
                'content_card.location' => 'Lieu',
                'content_card.cta.appels_a_projets' => 'Candidater',
                'content_card.cta.crowdfunding' => 'Contribuer',
                'content_card.cta.evenements' => 'Acheter',
                'content_card.cta.formations' => "S'inscrire",
                'content_card.cta.stands' => 'Réserver',
                'content_card.module.appels_a_projets' => 'Appels à projets',
                'content_card.module.crowdfunding' => 'Crowdfunding',
                'content_card.module.evenements' => 'Événements',
                'content_card.module.formations' => 'Formations',
                'content_card.module.stands' => 'Stands',
                'content_card.organizer_followed' => 'Orga suivie',
                'content_card.paid' => 'Payant',
                'content_card.price_from' => 'À partir de',
                'content_card.published_by' => 'Publié par',
                'content_card.remaining_seats' => '{count} restantes',
                'content_card.seats' => 'Places',
                'content_card.selection' => 'Sélection',
                'content_card.sold_out' => 'Épuisé',
                'content_card.trending' => 'Tendance',
                'footer.eyebrow' => 'Portail public',
                'home.organizers.description' => 'Chaque organisateur peut être valorisé comme une vraie page publique.',
                'home.popular.description' => "Les contenus qui ont reçu le plus de mentions j'aime cette semaine.",
                'listing.empty_body' => 'Élargissez la recherche ou revenez au catalogue complet.',
                'listing.empty_content_body' => 'Modifiez vos critères ou explorez les catalogues par module.',
                'listing.empty_content_title' => 'Aucun contenu ne correspond à ces filtres.',
                'listing.reset' => 'Réinitialiser',
                'listing.reset_filters' => 'Réinitialiser les filtres',
                'listing.results' => 'résultats',
            ],
            'en' => [
                'common.view_all' => 'View all',
                'content_card.date' => 'Date',
                'content_card.follow_organizer' => 'Follow organizer',
                'content_card.free' => 'Free',
                'content_card.location' => 'Location',
                'content_card.cta.appels_a_projets' => 'Apply',
                'content_card.cta.crowdfunding' => 'Contribute',
                'content_card.cta.evenements' => 'Buy',
                'content_card.cta.formations' => 'Register',
                'content_card.cta.stands' => 'Book',
                'content_card.module.appels_a_projets' => 'Calls for projects',
                'content_card.module.crowdfunding' => 'Crowdfunding',
                'content_card.module.evenements' => 'Events',
                'content_card.module.formations' => 'Training',
                'content_card.module.stands' => 'Stands',
                'content_card.organizer_followed' => 'Organizer followed',
                'content_card.paid' => 'Paid',
                'content_card.price_from' => 'From',
                'content_card.published_by' => 'Published by',
                'content_card.remaining_seats' => '{count} left',
                'content_card.seats' => 'Seats',
                'content_card.selection' => 'Selected',
                'content_card.sold_out' => 'Sold out',
                'content_card.trending' => 'Trending',
                'footer.eyebrow' => 'Public portal',
                'home.organizers.description' => 'Each organizer can be showcased as a real public profile.',
                'home.popular.description' => 'The content with the most likes this week.',
                'listing.empty_body' => 'Broaden your search or return to the full catalogue.',
                'listing.empty_content_body' => 'Change your filters or explore the catalogue by module.',
                'listing.empty_content_title' => 'No content matches these filters.',
                'listing.reset' => 'Reset',
                'listing.reset_filters' => 'Reset filters',
                'listing.results' => 'results',
            ],
        ];
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('platform_settings')) {
            return;
        }

        $connection = DB::connection('central');
        $now = now();

        $connection->table('platform_settings')
            ->where('group', 'seo')
            ->whereIn('key', ['meta_title', 'meta_description', 'meta_keywords', 'robots', 'open_graph', 'twitter'])
            ->delete();

        foreach ($this->settings() as $setting) {
            $existing = $connection->table('platform_settings')->where('key', $setting['key'])->first();

            $connection->table('platform_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => 'seo',
                    'value' => json_encode($setting['value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    'type' => 'json',
                    'is_public' => true,
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('platform_settings')) {
            return;
        }

        DB::connection('central')
            ->table('platform_settings')
            ->where('group', 'seo')
            ->whereIn('key', ['seo.defaults', 'seo.open_graph', 'seo.twitter', 'seo.robots', 'seo.structured_data', 'seo.sitemap'])
            ->delete();
    }

    private function settings(): array
    {
        return [
            [
                'key' => 'seo.defaults',
                'value' => [
                    'title' => 'Ticket - Plateforme de billetterie et de réservation',
                    'description' => 'Ticket centralise les événements, réservations, paiements, appels à projets et campagnes de financement participatif sur une plateforme publique sécurisée.',
                    'keywords' => ['billetterie', 'événements', 'réservations', 'paiement sécurisé', 'crowdfunding', 'SaaS'],
                    'canonical_url' => null,
                    'robots_index' => 'index',
                    'robots_follow' => 'follow',
                    'max_image_preview' => 'large',
                    'include_in_sitemap' => true,
                    'sitemap_priority' => 1,
                ],
            ],
            [
                'key' => 'seo.open_graph',
                'value' => [
                    'og_title' => 'Ticket - Public marketplace',
                    'og_description' => 'Découvrez, réservez, achetez ou soutenez des expériences publiées par des organisateurs vérifiés.',
                    'og_type' => 'website',
                    'og_image_url' => null,
                    'og_image_alt' => 'Aperçu de la plateforme Ticket',
                ],
            ],
            [
                'key' => 'seo.twitter',
                'value' => [
                    'twitter_card' => 'summary_large_image',
                    'twitter_title' => 'Ticket - Public marketplace',
                    'twitter_description' => 'Une plateforme publique pour événements, réservations, appels à projets et crowdfunding.',
                    'twitter_image_url' => null,
                    'twitter_site' => null,
                ],
            ],
            [
                'key' => 'seo.robots',
                'value' => [
                    'robots_index' => 'index',
                    'robots_follow' => 'follow',
                    'max_image_preview' => 'large',
                    'disallow_paths' => ['/compte', '/checkout'],
                    'allow_paths' => ['/', '/evenements', '/formations', '/stands', '/appels-a-projets', '/crowdfunding'],
                ],
            ],
            [
                'key' => 'seo.structured_data',
                'value' => [
                    'schema_type' => 'Organization',
                    'structured_data_json' => json_encode([
                        '@context' => 'https://schema.org',
                        '@type' => 'Organization',
                        'name' => 'Ticket',
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
            [
                'key' => 'seo.sitemap',
                'value' => [
                    'include_in_sitemap' => true,
                    'include_front_pages' => true,
                    'include_catalog' => true,
                    'include_organizers' => true,
                    'lastmod_strategy' => 'significant_content_update',
                ],
            ],
        ];
    }
};

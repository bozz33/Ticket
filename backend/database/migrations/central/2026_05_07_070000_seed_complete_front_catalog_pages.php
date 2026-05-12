<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = CarbonImmutable::now();

        foreach ($this->pages() as $page) {
            $this->upsertPage($connection, $page, $now);
        }

        foreach ($this->menuItems() as $item) {
            $this->upsertMenuItem($connection, $item, $now);
        }
    }

    public function down(): void
    {
        $connection = DB::connection('central');
        $keys = array_column($this->pages(), 'key');

        $connection->table('front_page_sections')
            ->whereIn('front_page_id', $connection->table('front_pages')->whereIn('key', $keys)->pluck('id'))
            ->delete();

        $connection->table('front_pages')->whereIn('key', $keys)->delete();
    }

    private function upsertPage(ConnectionInterface $connection, array $page, CarbonImmutable $now): void
    {
        $existing = $connection->table('front_pages')->where('key', $page['key'])->first();

        $connection->table('front_pages')->updateOrInsert(
            ['key' => $page['key']],
            [
                'title' => $page['title'],
                'route_path' => $page['route_path'],
                'slug' => $page['slug'],
                'template' => $page['template'],
                'status' => 'published',
                'is_active' => true,
                'show_in_sitemap' => true,
                'seo_title' => $page['seo_title'],
                'seo_description' => $page['seo_description'],
                'seo_image_url' => $page['seo_image_url'],
                'meta' => json_encode($page['meta'] ?? [], JSON_THROW_ON_ERROR),
                'published_at' => $existing?->published_at ?? $now,
                'created_at' => $existing?->created_at ?? $now,
                'updated_at' => $now,
            ],
        );

        $pageId = $connection->table('front_pages')->where('key', $page['key'])->value('id');

        foreach ($page['sections'] as $index => $section) {
            $existingSection = $connection->table('front_page_sections')
                ->where('front_page_id', $pageId)
                ->where('key', $section['key'])
                ->first();

            $connection->table('front_page_sections')->updateOrInsert(
                ['front_page_id' => $pageId, 'key' => $section['key']],
                [
                    'type' => $section['type'],
                    'title' => $section['title'] ?? null,
                    'eyebrow' => $section['eyebrow'] ?? null,
                    'body' => $section['body'] ?? null,
                    'image_url' => $section['image_url'] ?? null,
                    'primary_cta_label' => $section['primary_cta_label'] ?? null,
                    'primary_cta_url' => $section['primary_cta_url'] ?? null,
                    'secondary_cta_label' => $section['secondary_cta_label'] ?? null,
                    'secondary_cta_url' => $section['secondary_cta_url'] ?? null,
                    'sort_order' => $section['sort_order'] ?? ($index + 1),
                    'is_active' => $section['is_active'] ?? true,
                    'items' => json_encode($section['items'] ?? [], JSON_THROW_ON_ERROR),
                    'settings' => json_encode($section['settings'] ?? [], JSON_THROW_ON_ERROR),
                    'created_at' => $existingSection?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    private function upsertMenuItem(ConnectionInterface $connection, array $item, CarbonImmutable $now): void
    {
        $menuId = $connection->table('front_menus')->where('key', $item['menu'])->value('id');

        if (! $menuId) {
            return;
        }

        $existing = $connection->table('front_menu_items')
            ->where('front_menu_id', $menuId)
            ->where('href', $item['href'])
            ->first();

        $connection->table('front_menu_items')->updateOrInsert(
            ['front_menu_id' => $menuId, 'href' => $item['href']],
            [
                'label' => $item['label'],
                'target' => '_self',
                'sort_order' => $item['sort_order'],
                'is_active' => true,
                'meta' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => $existing?->created_at ?? $now,
                'updated_at' => $now,
            ],
        );
    }

    private function menuItems(): array
    {
        return [
            ['menu' => 'footer_explore', 'label' => 'Appels à projets', 'href' => '/appels-a-projets', 'sort_order' => 4],
            ['menu' => 'footer_explore', 'label' => 'Crowdfunding', 'href' => '/crowdfunding', 'sort_order' => 5],
            ['menu' => 'footer_platform', 'label' => 'Recherche', 'href' => '/recherche', 'sort_order' => 4],
        ];
    }

    private function pages(): array
    {
        $heroImage = 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1800&q=80';

        return [
            [
                'key' => 'home',
                'title' => 'Accueil',
                'route_path' => '/',
                'slug' => 'home',
                'template' => 'content_page',
                'seo_title' => 'Ticket | Portail public multi-modules',
                'seo_description' => 'Découvrez des événements, formations, stands, appels à projets et campagnes de crowdfunding sur un portail public unifié.',
                'seo_image_url' => $heroImage,
                'sections' => [
                    [
                        'key' => 'home_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Marketplace publique',
                        'title' => 'Des expériences à réserver, soutenir ou rejoindre.',
                        'body' => 'Un catalogue premium pour billets, formations, stands, candidatures et campagnes, avec des parcours d’achat clairs et une mise en avant forte des organisateurs.',
                        'image_url' => $heroImage,
                        'primary_cta_label' => 'Explorer le catalogue',
                        'primary_cta_url' => '/evenements',
                        'secondary_cta_label' => 'Publier sur la plateforme',
                        'secondary_cta_url' => '/devenir-organisateur',
                    ],
                    [
                        'key' => 'home_metrics',
                        'type' => 'metrics',
                        'items' => [
                            ['label' => 'Contenus publiés', 'value' => 'Dynamique'],
                            ['label' => 'Organisateurs', 'value' => 'Dynamique'],
                            ['label' => 'Utilisateurs', 'value' => 'Dynamique'],
                        ],
                    ],
                    [
                        'key' => 'home_featured',
                        'type' => 'feature_grid',
                        'eyebrow' => 'Sélection éditée',
                        'title' => 'À la une',
                        'body' => 'Une vitrine riche et visuelle, avec des cartes denses et des CTA directs.',
                    ],
                    [
                        'key' => 'home_popular',
                        'type' => 'feature_grid',
                        'eyebrow' => 'Tendances',
                        'title' => 'Populaires cette semaine',
                        'body' => 'Les contenus les plus consultés et les plus proches de la conversion.',
                    ],
                    [
                        'key' => 'home_organizers',
                        'type' => 'organizer_highlights',
                        'eyebrow' => 'Organisateurs',
                        'title' => 'Profils publics mis en avant',
                        'body' => 'Chaque tenant peut être valorisé comme une vraie page publique d’organisateur.',
                    ],
                ],
            ],
            $this->catalogPage('events_catalog', 'Événements', '/evenements', 'evenements', 'Ticket / événements', 'Billets, conférences, concerts et expériences à réserver.', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1800&q=80'),
            $this->catalogPage('training_catalog', 'Formations', '/formations', 'formations', 'Catalogue formation', 'Sessions, ateliers et parcours pédagogiques publiés par les organisateurs.', 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1800&q=80'),
            $this->catalogPage('stands_catalog', 'Stands', '/stands', 'stands', 'Espaces exposants', 'Réservez des stands, espaces partenaires ou espaces commerciaux.', 'https://images.unsplash.com/photo-1515168833906-d2a3b82b302b?auto=format&fit=crop&w=1800&q=80'),
            $this->catalogPage('calls_catalog', 'Appels à projets', '/appels-a-projets', 'appels-a-projets', 'Candidatures', 'Consultez les appels à projets, préparez votre dossier et soumettez votre candidature.', 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1800&q=80'),
            $this->catalogPage('crowdfunding_catalog', 'Crowdfunding', '/crowdfunding', 'crowdfunding', 'Financement participatif', 'Découvrez les campagnes à soutenir et suivez leur progression.', 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?auto=format&fit=crop&w=1800&q=80'),
            [
                'key' => 'categories_catalog',
                'title' => 'Catégories',
                'route_path' => '/categories',
                'slug' => 'categories',
                'template' => 'content_page',
                'seo_title' => 'Catégories — Ticket',
                'seo_description' => 'Explorez le catalogue public par catégorie.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1800&q=80',
                'sections' => [
                    [
                        'key' => 'categories_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Navigation',
                        'title' => 'Catégories',
                        'body' => 'Entrées éditoriales pour accélérer la découverte sur le portail public.',
                    ],
                ],
            ],
            [
                'key' => 'search_catalog',
                'title' => 'Recherche',
                'route_path' => '/recherche',
                'slug' => 'recherche',
                'template' => 'content_page',
                'seo_title' => 'Recherche — Ticket',
                'seo_description' => 'Recherchez dans tous les modules et tous les organisateurs.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1485217988980-11786ced9454?auto=format&fit=crop&w=1800&q=80',
                'sections' => [
                    [
                        'key' => 'search_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Recherche globale',
                        'title' => 'Tout le catalogue public',
                        'body' => 'Une seule recherche pour tous les modules et tous les organisateurs.',
                    ],
                ],
            ],
        ];
    }

    private function catalogPage(
        string $key,
        string $title,
        string $route,
        string $slug,
        string $eyebrow,
        string $body,
        string $image,
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'route_path' => $route,
            'slug' => $slug,
            'template' => 'content_page',
            'seo_title' => $title . ' — Ticket',
            'seo_description' => $body,
            'seo_image_url' => $image,
            'sections' => [
                [
                    'key' => $slug . '_hero',
                    'type' => 'hero',
                    'eyebrow' => $eyebrow,
                    'title' => $title,
                    'body' => $body,
                    'image_url' => $image,
                ],
                [
                    'key' => $slug . '_intro',
                    'type' => 'feature_grid',
                    'eyebrow' => 'Parcours public',
                    'title' => 'Ce que les visiteurs peuvent faire ici',
                    'body' => 'Ce bloc est administrable depuis le super-admin avec ses titres, textes, images et liens.',
                    'items' => [
                        ['title' => 'Découvrir', 'body' => 'Parcourir les fiches publiées et filtrer par catégorie, ville ou prix.'],
                        ['title' => 'Comparer', 'body' => 'Lire les détails, les dates, les conditions et les offres disponibles.'],
                        ['title' => 'Convertir', 'body' => 'Acheter, réserver, soutenir ou candidater selon le module activé.'],
                    ],
                ],
            ],
        ];
    }
};

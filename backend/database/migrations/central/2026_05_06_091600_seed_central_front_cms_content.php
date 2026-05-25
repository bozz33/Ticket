<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('central');
        $now = CarbonImmutable::now();

        foreach ($this->menus() as $menu) {
            $existing = $connection->table('front_menus')->where('key', $menu['key'])->first();

            $connection->table('front_menus')->updateOrInsert(
                ['key' => $menu['key']],
                [
                    'title' => $menu['title'],
                    'location' => $menu['location'],
                    'is_active' => $menu['is_active'],
                    'settings' => json_encode($menu['settings'], JSON_THROW_ON_ERROR),
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );

            $menuId = $connection->table('front_menus')->where('key', $menu['key'])->value('id');
            $connection->table('front_menu_items')->where('front_menu_id', $menuId)->delete();

            foreach ($menu['items'] as $index => $item) {
                $connection->table('front_menu_items')->insert([
                    'front_menu_id' => $menuId,
                    'label' => $item['label'],
                    'href' => $item['href'],
                    'target' => $item['target'] ?? '_self',
                    'sort_order' => $index + 1,
                    'is_active' => $item['is_active'] ?? true,
                    'meta' => json_encode($item['meta'] ?? [], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ($this->pages() as $page) {
            $existing = $connection->table('front_pages')->where('key', $page['key'])->first();

            $connection->table('front_pages')->updateOrInsert(
                ['key' => $page['key']],
                [
                    'title' => $page['title'],
                    'route_path' => $page['route_path'],
                    'slug' => $page['slug'],
                    'template' => $page['template'],
                    'status' => $page['status'],
                    'is_active' => true,
                    'show_in_sitemap' => true,
                    'seo_title' => $page['seo_title'],
                    'seo_description' => $page['seo_description'],
                    'seo_image_url' => $page['seo_image_url'] ?? null,
                    'meta' => json_encode($page['meta'] ?? [], JSON_THROW_ON_ERROR),
                    'published_at' => $page['status'] === 'published' ? $now : null,
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ],
            );

            $pageId = $connection->table('front_pages')->where('key', $page['key'])->value('id');
            $connection->table('front_page_sections')->where('front_page_id', $pageId)->delete();

            foreach ($page['sections'] as $index => $section) {
                $connection->table('front_page_sections')->insert([
                    'front_page_id' => $pageId,
                    'key' => $section['key'],
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
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $connection = DB::connection('central');

        $connection->table('front_page_sections')->whereIn('key', [
            'about_hero',
            'about_overview',
            'about_organizers',
            'contact_hero',
            'contact_channels',
            'contact_form',
            'contact_quick_help',
            'faq_hero',
            'faq_metrics',
            'faq_orders',
            'faq_payments',
            'faq_refunds',
            'faq_account',
            'faq_organizers',
            'faq_support',
            'legal_hero',
            'legal_editor',
            'legal_host',
            'legal_service',
            'legal_access',
            'legal_privacy',
            'legal_liability',
            'refund_hero',
            'refund_conditions',
            'refund_cancellations',
            'refund_customer_requests',
            'refund_processing',
            'refund_exclusions',
            'organizer_hero',
            'organizer_features',
            'organizer_form',
        ])->delete();

        $connection->table('front_pages')->whereIn('key', [
            'about',
            'contact',
            'faq',
            'legal_notices',
            'refund_policy',
            'become_organizer',
        ])->delete();

        $connection->table('front_menu_items')->whereIn('label', [
            'Accueil',
            'A propos',
            'Evenements',
            'Contact',
            'Remboursement',
            'FAQ',
            'Mentions legales',
            'Formations',
            'Stands',
            'Crowdfunding',
            'Categories',
            'Devenir organisateur',
            'Panel user',
            'Onboarding organisateur',
        ])->delete();

        $connection->table('front_menus')->whereIn('key', [
            'header_primary',
            'header_utility',
            'footer_explore',
            'footer_platform',
            'footer_bottom',
        ])->delete();
    }

    private function menus(): array
    {
        return [
            [
                'key' => 'header_primary',
                'title' => 'Header principal',
                'location' => 'header_primary',
                'is_active' => true,
                'settings' => [],
                'items' => [
                    ['label' => 'Accueil', 'href' => '/'],
                    ['label' => 'A propos', 'href' => '/a-propos'],
                    ['label' => 'Evenements', 'href' => '/evenements'],
                    ['label' => 'Contact', 'href' => '/contact'],
                ],
            ],
            [
                'key' => 'header_utility',
                'title' => 'Header utilitaire',
                'location' => 'header_utility',
                'is_active' => true,
                'settings' => [],
                'items' => [
                    ['label' => 'A propos', 'href' => '/a-propos'],
                    ['label' => 'Remboursement', 'href' => '/remboursement'],
                    ['label' => 'FAQ', 'href' => '/faq'],
                    ['label' => 'Mentions legales', 'href' => '/mentions-legales'],
                ],
            ],
            [
                'key' => 'footer_explore',
                'title' => 'Footer explorer',
                'location' => 'footer_explore',
                'is_active' => true,
                'settings' => [],
                'items' => [
                    ['label' => 'Evenements', 'href' => '/evenements'],
                    ['label' => 'Formations', 'href' => '/formations'],
                    ['label' => 'Stands', 'href' => '/stands'],
                    ['label' => 'Crowdfunding', 'href' => '/crowdfunding'],
                ],
            ],
            [
                'key' => 'footer_platform',
                'title' => 'Footer plateforme',
                'location' => 'footer_platform',
                'is_active' => true,
                'settings' => [],
                'items' => [
                    ['label' => 'Categories', 'href' => '/categories'],
                    ['label' => 'A propos', 'href' => '/a-propos'],
                    ['label' => 'Devenir organisateur', 'href' => '/devenir-organisateur'],
                ],
            ],
            [
                'key' => 'footer_bottom',
                'title' => 'Footer bas de page',
                'location' => 'footer_bottom',
                'is_active' => true,
                'settings' => [],
                'items' => [
                    ['label' => 'Contact', 'href' => '/contact'],
                    ['label' => 'Panel user', 'href' => '/compte'],
                    ['label' => 'Onboarding organisateur', 'href' => '/devenir-organisateur'],
                ],
            ],
        ];
    }

    private function pages(): array
    {
        return [
            [
                'key' => 'about',
                'title' => 'A propos',
                'route_path' => '/a-propos',
                'slug' => 'a-propos',
                'template' => 'marketing_page',
                'status' => 'published',
                'seo_title' => 'A propos — Ticket',
                'seo_description' => 'Une plateforme publique pour vendre, réserver et soutenir sur plusieurs modules.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1800&q=80',
                'meta' => [
                    'summary' => 'Plateforme publique unifiée pour vendre, réserver, candidater et soutenir.',
                ],
                'sections' => [
                    [
                        'key' => 'about_hero',
                        'type' => 'hero',
                        'eyebrow' => 'A propos',
                        'title' => 'Une plateforme publique pour vendre, reserver et soutenir.',
                        'body' => 'Ticket rassemble des expériences live, des parcours de candidature, des offres de formation et des campagnes de financement dans un même front public.',
                        'image_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1800&q=80',
                        'settings' => ['variant' => 'page'],
                    ],
                    [
                        'key' => 'about_overview',
                        'type' => 'split_overview',
                        'eyebrow' => 'Notre approche',
                        'title' => 'Des pages transactionnelles qui restent desirables.',
                        'body' => 'Le projet mélange une direction premium avec une logique marketplace plus directe, afin de garder de la chaleur visuelle sans alourdir le parcours utilisateur.',
                        'primary_cta_label' => 'Explorer les evenements',
                        'primary_cta_url' => '/evenements',
                        'secondary_cta_label' => 'Devenir organisateur',
                        'secondary_cta_url' => '/devenir-organisateur',
                        'items' => [
                            ['title' => 'Front public unifié pour tous les organisateurs'],
                            ['title' => 'Pages détail denses avec CTA visibles sans friction'],
                            ['title' => 'Checkout rassurant, mobile-first et lisible'],
                            ['title' => 'Valorisation publique des organisateurs et intervenants'],
                            ['label' => 'Support', 'value' => 'support@ticket.africa'],
                            ['label' => 'Paiement', 'value' => 'Carte bancaire, Mobile Money'],
                            ['label' => 'Experience', 'value' => 'Catalogue, détail, checkout et espace public organisateur'],
                        ],
                        'settings' => ['layout' => 'aside_facts'],
                    ],
                    [
                        'key' => 'about_organizers',
                        'type' => 'organizer_highlights',
                        'eyebrow' => 'Organisateurs',
                        'title' => 'Des profils publics qui comptent autant que les contenus.',
                        'body' => 'Chaque structure publie dans le portail commun tout en gardant sa propre vitrine.',
                        'settings' => ['max_items' => 3],
                    ],
                ],
            ],
            [
                'key' => 'contact',
                'title' => 'Contact',
                'route_path' => '/contact',
                'slug' => 'contact',
                'template' => 'contact_page',
                'status' => 'published',
                'seo_title' => 'Contact & Support — Ticket',
                'seo_description' => 'Contactez l’équipe Ticket pour le support, les partenariats et les questions organisateurs.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1800&q=80',
                'meta' => ['summary' => 'Nous sommes là pour vous accompagner.'],
                'sections' => [
                    [
                        'key' => 'contact_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Support client',
                        'title' => 'Nous sommes la pour vous',
                        'body' => 'Une question, un problème ou une demande de partenariat ? Notre équipe est disponible pour vous accompagner.',
                        'image_url' => 'https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1800&q=80',
                        'settings' => ['variant' => 'inner'],
                    ],
                    [
                        'key' => 'contact_channels',
                        'type' => 'contact_channels',
                        'eyebrow' => 'Canaux disponibles',
                        'title' => 'Comment nous joindre',
                        'body' => 'Choisissez le canal le plus adapté à votre besoin.',
                        'items' => [
                            ['label' => 'Email support', 'value' => 'support@ticket.africa', 'body' => 'Réponse sous 24-48h ouvrées', 'href' => 'mailto:support@ticket.africa', 'icon' => 'mail'],
                            ['label' => 'Telephone', 'value' => '+225 27 22 40 11 00', 'body' => 'Disponible 24h/24, 7j/7', 'href' => 'tel:+2252722401100', 'icon' => 'phone'],
                            ['label' => 'WhatsApp', 'value' => 'Ouvrir la conversation', 'body' => 'Réponses rapides en journée', 'href' => 'https://wa.me/2252722401100', 'icon' => 'whatsapp'],
                            ['label' => 'Horaires', 'value' => '24h/24 — 7j/7', 'body' => 'Support email et téléphonique', 'icon' => 'clock'],
                            ['label' => 'Urgence', 'value' => '< 2 heures', 'body' => 'Paiement débité ou billet manquant'],
                            ['label' => 'Demande générale', 'value' => '< 48 heures', 'body' => 'Traitement standard support'],
                        ],
                        'settings' => ['layout' => 'two_columns'],
                    ],
                    [
                        'key' => 'contact_form',
                        'type' => 'contact_form',
                        'eyebrow' => 'Formulaire de contact',
                        'title' => 'Envoyez-nous un message',
                        'body' => 'Décrivez votre situation en détail pour un traitement plus rapide.',
                        'items' => [
                            ['label' => 'commande'],
                            ['label' => 'paiement'],
                            ['label' => 'remboursement'],
                            ['label' => 'billet'],
                            ['label' => 'organisateur'],
                            ['label' => 'technique'],
                            ['label' => 'autre'],
                        ],
                        'settings' => ['success_note' => 'Réponse garantie sous 48h ouvrées · Données sécurisées'],
                    ],
                    [
                        'key' => 'contact_quick_help',
                        'type' => 'feature_grid',
                        'eyebrow' => 'Aide rapide',
                        'title' => 'Questions fréquentes',
                        'body' => 'Retrouvez les réponses les plus courantes ou explorez la FAQ complète.',
                        'items' => [
                            ['title' => 'Billet non reçu', 'body' => 'Vérifiez vos spams puis connectez-vous à votre compte pour retrouver la commande.', 'href' => '/faq#commandes', 'label' => 'En savoir plus'],
                            ['title' => 'Remboursement', 'body' => 'Le remboursement dépend de la politique de l’organisateur ou d’une annulation.', 'href' => '/remboursement', 'label' => 'En savoir plus'],
                            ['title' => 'Problème de paiement', 'body' => 'Si votre compte a été débité sans confirmation, contactez-nous avec la référence de transaction.', 'href' => '/faq#paiements', 'label' => 'En savoir plus'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'faq',
                'title' => 'FAQ',
                'route_path' => '/faq',
                'slug' => 'faq',
                'template' => 'faq_page',
                'status' => 'published',
                'seo_title' => 'FAQ — Ticket',
                'seo_description' => 'Retrouvez les réponses essentielles sur les commandes, les paiements, les remboursements et le compte acheteur.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1800&q=80',
                'meta' => ['summary' => 'Centre d’aide Ticket'],
                'sections' => [
                    [
                        'key' => 'faq_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Centre d’aide',
                        'title' => 'Questions frequentes',
                        'body' => 'Retrouvez les réponses essentielles sur les commandes, les paiements, les remboursements, le compte acheteur et l’espace organisateur.',
                        'image_url' => 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1800&q=80',
                        'settings' => ['variant' => 'inner'],
                    ],
                    [
                        'key' => 'faq_metrics',
                        'type' => 'metrics',
                        'eyebrow' => 'FAQ',
                        'title' => 'Vue d’ensemble',
                        'items' => [
                            ['label' => 'Themes couverts', 'value' => '6'],
                            ['label' => 'Questions traitees', 'value' => '18'],
                            ['label' => 'Support direct', 'value' => '24/7'],
                        ],
                    ],
                    [
                        'key' => 'faq_orders',
                        'type' => 'faq',
                        'title' => 'Commandes et billets',
                        'body' => 'Tout ce qui concerne l’achat, la réception du billet et le suivi de commande.',
                        'items' => [
                            ['title' => 'Je n’ai pas reçu mon billet après paiement. Que faire ?', 'body' => 'Vérifiez votre boîte mail principale, vos spams et l’onglet promotions. Connectez-vous ensuite à votre espace acheteur. Si aucun billet n’apparaît, contactez le support avec votre référence de commande.'],
                            ['title' => 'Comment retrouver une commande déjà passée ?', 'body' => 'Depuis votre compte, ouvrez la section Mes commandes pour retrouver l’historique, le statut de paiement et les billets disponibles.'],
                            ['title' => 'Puis-je transférer mon billet à une autre personne ?', 'body' => 'Le transfert dépend des règles définies par l’organisateur. Si l’événement l’autorise, la procédure sera indiquée dans les détails de la commande.'],
                        ],
                    ],
                    [
                        'key' => 'faq_payments',
                        'type' => 'faq',
                        'title' => 'Paiements et sécurité',
                        'body' => 'Informations utiles sur les moyens de paiement, les incidents et la sécurité de vos transactions.',
                        'items' => [
                            ['title' => 'Mon compte a été débité mais je n’ai pas de confirmation. Pourquoi ?', 'body' => 'Il peut s’agir d’un délai de confirmation entre la passerelle de paiement et la plateforme. Attendez quelques minutes puis actualisez votre espace acheteur.'],
                            ['title' => 'Quels moyens de paiement sont acceptés ?', 'body' => 'Les moyens disponibles dépendent de la configuration active pour l’organisateur et du pays concerné.'],
                            ['title' => 'Le paiement sur Ticket est-il sécurisé ?', 'body' => 'Oui. Les flux critiques passent par des contrôles de sécurité, une validation d’origine côté front et des vérifications côté backend.'],
                        ],
                    ],
                    [
                        'key' => 'faq_refunds',
                        'type' => 'faq',
                        'title' => 'Remboursements',
                        'body' => 'Comprendre quand un remboursement est possible et comment il est traité.',
                        'items' => [
                            ['title' => 'Dans quels cas puis-je demander un remboursement ?', 'body' => 'Les remboursements dépendent de la politique de l’organisateur et du contexte de la commande : annulation, report, doublon ou incident confirmé.'],
                            ['title' => 'Combien de temps prend un remboursement ?', 'body' => 'Le délai varie selon le moyen de paiement initial, généralement entre 24 heures et 10 jours ouvrés.'],
                            ['title' => 'Où suivre l’état d’une demande de remboursement ?', 'body' => 'Le suivi se fait depuis votre compte acheteur lorsqu’elle est rattachée à une commande existante.'],
                        ],
                    ],
                    [
                        'key' => 'faq_account',
                        'type' => 'faq',
                        'title' => 'Compte acheteur',
                        'body' => 'Création de compte, connexion, sécurité et récupération d’accès.',
                        'items' => [
                            ['title' => 'Comment créer mon compte acheteur ?', 'body' => 'Rendez-vous sur la page d’inscription et complétez votre nom, votre email et un mot de passe sécurisé.'],
                            ['title' => 'J’ai oublié mon mot de passe. Que dois-je faire ?', 'body' => 'Utilisez le lien Mot de passe oublié depuis la page de connexion pour recevoir un lien de réinitialisation.'],
                            ['title' => 'Puis-je changer l’adresse email de mon compte ?', 'body' => 'Si l’option n’est pas encore proposée directement dans votre espace, contactez le support pour une vérification complémentaire.'],
                        ],
                    ],
                    [
                        'key' => 'faq_organizers',
                        'type' => 'faq',
                        'title' => 'Organisateurs et publications',
                        'body' => 'Questions fréquentes pour les porteurs de projet et les futurs organisateurs.',
                        'items' => [
                            ['title' => 'Comment devenir organisateur sur Ticket ?', 'body' => 'Rendez-vous sur la page Devenir organisateur puis complétez le formulaire de création d’espace.'],
                            ['title' => 'Les catégories visibles sur le catalogue public viennent d’où ?', 'body' => 'Elles proviennent des catégories configurées au niveau d’administration et rendues disponibles selon le module concerné.'],
                            ['title' => 'Puis-je gérer plusieurs types de contenus avec le même espace ?', 'body' => 'Oui. Un organisateur peut publier sur plusieurs modules selon ses droits et sa configuration.'],
                        ],
                    ],
                    [
                        'key' => 'faq_support',
                        'type' => 'faq',
                        'title' => 'Support et assistance',
                        'body' => 'Quand et comment joindre l’équipe Ticket pour une aide rapide.',
                        'items' => [
                            ['title' => 'Comment contacter le support ?', 'body' => 'Vous pouvez utiliser la page Contact, envoyer un email au support ou appeler le numéro affiché dans le header.'],
                            ['title' => 'Quels éléments transmettre pour un traitement rapide ?', 'body' => 'Indiquez l’email utilisé lors de l’achat, la référence de commande, le nom de l’événement et le problème rencontré.'],
                            ['title' => 'Le support est-il disponible 24h/24 ?', 'body' => 'Les canaux de contact sont présentés comme disponibles en continu, mais les délais peuvent varier selon la charge.'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'legal_notices',
                'title' => 'Mentions légales',
                'route_path' => '/mentions-legales',
                'slug' => 'mentions-legales',
                'template' => 'legal_page',
                'status' => 'published',
                'seo_title' => 'Mentions légales — Ticket',
                'seo_description' => 'Mentions légales de la plateforme Ticket : éditeur, hébergeur, propriété intellectuelle et données personnelles.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1800&q=80',
                'meta' => ['summary' => 'Informations légales relatives à la plateforme Ticket.'],
                'sections' => [
                    [
                        'key' => 'legal_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Documents légaux',
                        'title' => 'Mentions legales',
                        'body' => 'Conformément aux dispositions légales en vigueur, vous trouverez ci-dessous les informations légales relatives à la plateforme Ticket.',
                        'image_url' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1800&q=80',
                        'settings' => ['variant' => 'inner', 'updated_at_label' => 'Dernière mise à jour : 1er avril 2025'],
                    ],
                    [
                        'key' => 'legal_editor',
                        'type' => 'legal_article',
                        'title' => 'Éditeur du site',
                        'body' => 'Le site ticket.africa est édité par Ticket Africa SAS, société par actions simplifiée basée à Abidjan. Contact principal : contact@ticket.africa · +225 27 22 40 11 00.',
                        'items' => [
                            ['label' => 'Raison sociale', 'value' => 'Ticket Africa SAS'],
                            ['label' => 'Siège social', 'value' => 'Plateau, Avenue Marchand — Abidjan, Côte d’Ivoire'],
                            ['label' => 'RCCM', 'value' => 'CI-ABJ-2024-B-12345'],
                            ['label' => 'N° contribuable', 'value' => '2024123456789'],
                        ],
                    ],
                    [
                        'key' => 'legal_host',
                        'type' => 'legal_article',
                        'title' => 'Hébergement',
                        'body' => 'Le site est hébergé sur une infrastructure cloud sécurisée avec supervision et journalisation centralisée. Région de données privilégiée : Europe (Paris).',
                        'items' => [
                            ['label' => 'Hébergeur', 'value' => 'Amazon Web Services (AWS)'],
                            ['label' => 'Adresse', 'value' => '410 Terry Ave N, Seattle, WA 98109, États-Unis'],
                            ['label' => 'Région', 'value' => 'Europe (Paris) — eu-west-3'],
                        ],
                    ],
                    [
                        'key' => 'legal_service',
                        'type' => 'legal_article',
                        'title' => 'Objet et propriété intellectuelle',
                        'body' => 'Ticket est une plateforme multi-organisateurs dédiée à la gestion et à la commercialisation d’événements, formations, stands, appels à projets et campagnes de financement participatif. Les éléments de marque, d’interface et de code sont protégés par les règles de propriété intellectuelle.',
                    ],
                    [
                        'key' => 'legal_access',
                        'type' => 'legal_article',
                        'title' => 'Accès au service',
                        'body' => 'L’accès au catalogue public est libre. La création d’un compte utilisateur peut être requise pour certaines opérations sécurisées, notamment le suivi de commandes et certaines interactions post-achat.',
                        'items' => [
                            ['title' => 'Âge minimum', 'body' => 'L’utilisation de la plateforme est réservée aux majeurs ou aux mineurs agissant sous la supervision d’un représentant légal.'],
                            ['title' => 'Maintenance', 'body' => 'La plateforme peut être temporairement indisponible pour maintenance, sécurité ou évolution.'],
                        ],
                    ],
                    [
                        'key' => 'legal_privacy',
                        'type' => 'legal_article',
                        'title' => 'Données personnelles & RGPD',
                        'body' => 'Ticket collecte et traite des données nécessaires à la gestion des comptes, commandes, billets, remboursements et communications transactionnelles. Les traitements sont effectués dans le respect du RGPD et des lois locales applicables.',
                        'items' => [
                            ['title' => 'Données collectées', 'body' => 'Nom, prénom, email, téléphone, historiques de transaction, journaux techniques, préférences et consentements.'],
                            ['title' => 'Finalités', 'body' => 'Authentification, traitement des commandes, support, remboursements, communications transactionnelles et amélioration de la plateforme.'],
                            ['title' => 'Droits', 'body' => 'Accès, rectification, effacement, opposition, portabilité et limitation. Contact DPO : dpo@ticket.africa.'],
                        ],
                    ],
                    [
                        'key' => 'legal_liability',
                        'type' => 'legal_article',
                        'title' => 'Responsabilité, cookies et droit applicable',
                        'body' => 'Ticket agit en qualité d’intermédiaire technique et commercial. L’utilisation du service suppose l’acceptation des politiques applicables, notamment en matière de cookies, de responsabilité et de droit applicable.',
                        'items' => [
                            ['title' => 'Cookies', 'body' => 'Cookies essentiels, fonctionnels et analytiques conformément à la configuration active et au consentement recueilli.'],
                            ['title' => 'Responsabilité', 'body' => 'La responsabilité relative au contenu des événements et publications incombe prioritairement aux organisateurs.'],
                            ['title' => 'Droit applicable', 'body' => 'Le droit applicable et la juridiction compétente sont précisés dans les conditions contractuelles de la plateforme.'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'refund_policy',
                'title' => 'Politique de remboursement',
                'route_path' => '/remboursement',
                'slug' => 'remboursement',
                'template' => 'legal_page',
                'status' => 'published',
                'seo_title' => 'Politique de remboursement — Ticket',
                'seo_description' => 'Conditions, délais et modalités de remboursement applicables sur la plateforme Ticket.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1800&q=80',
                'meta' => ['summary' => 'Conditions, délais et modalités de remboursement.'],
                'sections' => [
                    [
                        'key' => 'refund_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Documents légaux',
                        'title' => 'Politique de remboursement',
                        'body' => 'Cette politique définit les conditions dans lesquelles un remboursement peut être accordé sur la plateforme Ticket.',
                        'image_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1800&q=80',
                        'settings' => ['variant' => 'inner', 'updated_at_label' => 'Dernière mise à jour : 1er avril 2025'],
                    ],
                    [
                        'key' => 'refund_conditions',
                        'type' => 'legal_article',
                        'title' => 'Conditions générales',
                        'body' => 'Un remboursement peut être accordé notamment en cas d’annulation, de report incompatible, de politique organisateur favorable, d’incident technique confirmé ou de débit sans confirmation de commande.',
                        'items' => [
                            ['label' => 'Annulation organisateur', 'value' => 'Oui — intégral'],
                            ['label' => 'Report d’événement', 'value' => 'Selon politique organisateur'],
                            ['label' => 'Demande acheteur', 'value' => 'Selon politique organisateur'],
                            ['label' => 'No-show', 'value' => 'Non'],
                            ['label' => 'Doublon / erreur de paiement', 'value' => 'Oui — intégral'],
                        ],
                    ],
                    [
                        'key' => 'refund_cancellations',
                        'type' => 'legal_article',
                        'title' => 'Annulation ou report par l’organisateur',
                        'body' => 'En cas d’annulation définitive, Ticket notifie les acheteurs et initie le remboursement intégral selon les délais applicables. En cas de report, l’acheteur peut conserver son billet ou demander un remboursement selon les conditions annoncées.',
                    ],
                    [
                        'key' => 'refund_customer_requests',
                        'type' => 'legal_article',
                        'title' => 'Demande de remboursement par l’acheteur',
                        'body' => 'L’acheteur peut soumettre une demande de remboursement depuis son compte lorsque l’option est disponible, ou contacter le support dans les autres cas.',
                        'items' => [
                            ['title' => 'Étape 1', 'body' => 'Connectez-vous à votre compte Ticket.'],
                            ['title' => 'Étape 2', 'body' => 'Accédez à Mes commandes et sélectionnez la commande concernée.'],
                            ['title' => 'Étape 3', 'body' => 'Soumettez votre motif si l’option est disponible.'],
                            ['title' => 'Étape 4', 'body' => 'Vous recevez une confirmation de traitement.'],
                        ],
                    ],
                    [
                        'key' => 'refund_processing',
                        'type' => 'legal_article',
                        'title' => 'Délais et modes de remboursement',
                        'body' => 'Une fois approuvé, le remboursement suit les délais de la passerelle et du moyen de paiement initial.',
                        'items' => [
                            ['label' => 'Carte bancaire', 'value' => '5 à 10 jours ouvrés'],
                            ['label' => 'Orange Money', 'value' => '24 à 72 heures'],
                            ['label' => 'MTN Mobile Money', 'value' => '24 à 72 heures'],
                            ['label' => 'Wave', 'value' => '24 à 48 heures'],
                            ['label' => 'Moov Money', 'value' => '24 à 72 heures'],
                        ],
                    ],
                    [
                        'key' => 'refund_exclusions',
                        'type' => 'legal_article',
                        'title' => 'Exclusions, cas particuliers et litiges',
                        'body' => 'Les billets non utilisés après la tenue de l’événement ne sont généralement pas remboursables. Les cas particuliers et contestations sont examinés au regard des politiques organisateur, du contexte contractuel et des preuves fournies.',
                    ],
                ],
            ],
            [
                'key' => 'become_organizer',
                'title' => 'Devenir organisateur',
                'route_path' => '/devenir-organisateur',
                'slug' => 'devenir-organisateur',
                'template' => 'onboarding_page',
                'status' => 'published',
                'seo_title' => 'Devenir organisateur — Ticket',
                'seo_description' => 'Publiez et vendez sur la plateforme Ticket avec un espace organisateur et un front public unifié.',
                'seo_image_url' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=1800&q=80',
                'meta' => ['summary' => 'Créer un espace organisateur sur Ticket.'],
                'sections' => [
                    [
                        'key' => 'organizer_hero',
                        'type' => 'hero',
                        'eyebrow' => 'Onboarding organisateur',
                        'title' => 'Publier et vendre sur la plateforme',
                        'body' => 'Un front public unifié, un espace organisateur autonome et des parcours de conversion cohérents sur tous les modules.',
                        'image_url' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=1800&q=80',
                        'settings' => ['variant' => 'page'],
                    ],
                    [
                        'key' => 'organizer_features',
                        'type' => 'feature_grid',
                        'eyebrow' => 'Modules',
                        'title' => 'Ce que vous pilotez depuis votre espace',
                        'items' => [
                            ['title' => 'Billetterie et checkout', 'body' => 'Vendre des billets, configurer plusieurs offres et suivre vos conversions.'],
                            ['title' => 'Stands, appels et crowdfunding', 'body' => 'Une même base produit pour plusieurs modules métier sans casser l’expérience publique.'],
                            ['title' => 'Pages organisateur', 'body' => 'Chaque organisateur dispose d’une vitrine publique dédiée dans le portail commun.'],
                        ],
                    ],
                    [
                        'key' => 'organizer_form',
                        'type' => 'onboarding_form',
                        'eyebrow' => 'Commencer maintenant',
                        'title' => 'Créer votre espace organisateur',
                        'body' => 'Votre espace est créé instantanément. Vous pourrez publier votre premier contenu dans les minutes qui suivent.',
                        'settings' => ['mount_form' => true],
                    ],
                ],
            ],
        ];
    }
};

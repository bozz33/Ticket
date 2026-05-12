<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Event;
use App\Models\Offer;
use App\Models\OrganizationProfile;
use App\Models\Tenant;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$tenantSlug = $argv[1] ?? 'vvvv';

$tenant = Tenant::query()->where('slug', $tenantSlug)->first();

if ($tenant === null) {
    fwrite(STDERR, "Tenant introuvable: {$tenantSlug}" . PHP_EOL);
    exit(1);
}

$offerCode = static function (string $slug, string $eventSlug, string $offerName): string {
    return strtoupper(Str::slug($slug . '-' . $eventSlug . '-' . $offerName, '-'));
};

$events = [
    [
        'slug' => 'summit-demo-2026',
        'title' => 'Summit Demo 2026',
        'summary' => 'Un evenement premium de demonstration pour valider le front acheteur, le checkout et les parcours de commande.',
        'description' => 'Ce contenu de test permet de valider l affichage catalogue, la fiche detail, le checkout, les recus et les commandes cote acheteur.',
        'venue_name' => 'Palais des Congres Demo',
        'venue_address' => 'Plateau, Abidjan',
        'cover_image_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1600&q=80',
        'published_at' => '2026-05-04 11:36:29',
        'starts_at' => '2026-05-14 09:00:00',
        'ends_at' => '2026-05-14 18:00:00',
        'meta' => [
            'featured' => true,
            'popular' => true,
            'badges' => ['Payant', 'Nouveau', 'Local'],
            'highlights' => ['Networking', 'Billetterie', 'Demo'],
            'gallery' => [
                'https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1400&q=80',
                'https://images.unsplash.com/photo-1515169067868-5387ec356754?auto=format&fit=crop&w=1400&q=80',
                'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1400&q=80',
            ],
            'speakers' => [
                ['name' => 'Awa Kone', 'role' => 'Product Lead', 'imageUrl' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80'],
                ['name' => 'Moussa Traore', 'role' => 'Ops Manager', 'imageUrl' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80'],
            ],
            'timeline' => [
                ['label' => 'Ouverture des portes', 'dateLabel' => '08:30', 'description' => 'Accueil et orientation des visiteurs'],
                ['label' => 'Pleniere', 'dateLabel' => '10:00', 'description' => 'Presentation des nouveautes Ticket'],
                ['label' => 'Networking', 'dateLabel' => '17:00', 'description' => 'Rencontres et demonstrations live'],
            ],
            'faq' => [
                ['question' => 'Billet nominatif ?', 'answer' => 'Oui, chaque billet est associe a un acheteur.'],
                ['question' => 'Acces mobile ?', 'answer' => 'Oui, le QR code est disponible apres validation.'],
            ],
            'program' => ['Accueil premium', 'Conferences produit', 'Sessions networking'],
        ],
        'offers' => [
            [
                'name' => 'Pass Standard',
                'code' => 'VVVV-STANDARD-2026',
                'description' => 'Acces journee complete et badge visiteur.',
                'price_amount' => 15000,
                'quantity_total' => 120,
                'sort_order' => 1,
                'meta' => ['perks' => ['Badge visiteur', 'Acces conferences']],
            ],
            [
                'name' => 'Pass VIP',
                'code' => 'VVVV-VIP-2026',
                'description' => 'Acces premium, siege reserve et cocktail networking.',
                'price_amount' => 35000,
                'quantity_total' => 40,
                'sort_order' => 2,
                'meta' => ['perks' => ['Siege premium', 'Cocktail', 'Networking prive']],
            ],
        ],
    ],
    [
        'slug' => 'summit-demo-paid-2026',
        'title' => 'Summit Demo Payant 2026',
        'summary' => 'Un evenement premium payant pour valider le workflow complet cote acheteur.',
        'description' => 'Ce contenu de test permet de valider l affichage catalogue, la fiche detail et le checkout payant cote acheteur.',
        'venue_name' => 'Palais des Congres Demo',
        'venue_address' => 'Plateau, Abidjan',
        'cover_image_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1600&q=80',
        'published_at' => '2026-05-06 07:05:57',
        'starts_at' => '2026-05-16 09:00:00',
        'ends_at' => '2026-05-16 18:00:00',
        'meta' => [
            'featured' => true,
            'popular' => true,
            'badges' => ['Payant', 'Premium'],
            'highlights' => ['Networking', 'Billetterie', 'Paiement'],
            'gallery' => [
                'https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1400&q=80',
                'https://images.unsplash.com/photo-1515169067868-5387ec356754?auto=format&fit=crop&w=1400&q=80',
                'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1400&q=80',
            ],
            'speakers' => [
                ['name' => 'Awa Kone', 'role' => 'Product Lead', 'imageUrl' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80'],
                ['name' => 'Moussa Traore', 'role' => 'Ops Manager', 'imageUrl' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80'],
            ],
            'timeline' => [
                ['label' => 'Ouverture des portes', 'dateLabel' => '08:30', 'description' => 'Accueil et orientation des visiteurs'],
                ['label' => 'Pleniere', 'dateLabel' => '10:00', 'description' => 'Presentation des nouveautes Ticket'],
                ['label' => 'Networking', 'dateLabel' => '17:00', 'description' => 'Rencontres et demonstrations live'],
            ],
            'faq' => [
                ['question' => 'Billet nominatif ?', 'answer' => 'Oui, chaque billet est associe a un acheteur.'],
                ['question' => 'Acces mobile ?', 'answer' => 'Oui, le QR code est disponible apres validation.'],
            ],
            'program' => ['Accueil premium', 'Conferences produit', 'Sessions networking'],
        ],
        'offers' => [
            [
                'name' => 'Pass Standard',
                'code' => 'VVVV-PAID-STANDARD-2026',
                'description' => 'Acces journee complete et badge visiteur.',
                'price_amount' => 15000,
                'quantity_total' => 120,
                'sort_order' => 1,
                'meta' => ['perks' => ['Badge visiteur', 'Acces conferences']],
            ],
            [
                'name' => 'Pass VIP',
                'code' => 'VVVV-PAID-VIP-2026',
                'description' => 'Acces premium, siege reserve et cocktail networking.',
                'price_amount' => 35000,
                'quantity_total' => 40,
                'sort_order' => 2,
                'meta' => ['perks' => ['Siege premium', 'Cocktail', 'Networking prive']],
            ],
        ],
    ],
    [
        'slug' => 'summit-demo-free-2026',
        'title' => 'Summit Demo Gratuit 2026',
        'summary' => 'Un evenement gratuit pour tester le workflow sans paiement.',
        'description' => 'Ce contenu de test permet de valider la reservation gratuite et les commandes cote acheteur.',
        'venue_name' => 'Agora Demo',
        'venue_address' => 'Cocody, Abidjan',
        'cover_image_url' => 'https://images.unsplash.com/photo-1515169067868-5387ec356754?auto=format&fit=crop&w=1400&q=80',
        'published_at' => '2026-05-06 07:05:57',
        'starts_at' => '2026-05-20 10:00:00',
        'ends_at' => '2026-05-20 14:00:00',
        'meta' => [
            'featured' => false,
            'popular' => true,
            'badges' => ['Gratuit'],
            'highlights' => ['Decouverte', 'Acces libre'],
            'gallery' => [
                'https://images.unsplash.com/photo-1515169067868-5387ec356754?auto=format&fit=crop&w=1400&q=80',
                'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1400&q=80',
            ],
            'timeline' => [
                ['label' => 'Ouverture', 'dateLabel' => '10:00', 'description' => 'Accueil du public'],
                ['label' => 'Session live', 'dateLabel' => '11:00', 'description' => 'Presentation gratuite'],
            ],
            'faq' => [
                ['question' => 'Entree gratuite ?', 'answer' => 'Oui, l inscription standard est gratuite.'],
            ],
            'program' => ['Accueil', 'Demo produit', 'Questions reponses'],
        ],
        'offers' => [
            [
                'name' => 'Pass Gratuit',
                'code' => 'VVVV-FREE-2026',
                'description' => 'Reservation gratuite pour test du workflow.',
                'price_amount' => 0,
                'quantity_total' => 300,
                'sort_order' => 1,
                'meta' => ['perks' => ['Acces libre', 'Badge digital']],
            ],
        ],
    ],
];

$tenant->run(function () use ($tenantSlug, $events, $offerCode): void {
    $categoryId = Category::query()->where('slug', 'concert')->value('id') ?? Category::query()->min('id');

    $organization = OrganizationProfile::query()->firstOrCreate(
        ['email' => 'hello@' . $tenantSlug . '.local'],
        [
            'legal_name' => strtoupper($tenantSlug) . ' Demo Organization',
            'display_name' => strtoupper($tenantSlug),
            'description' => 'Organisation de demonstration pour les tests catalogue, detail et checkout.',
            'phone' => '+225 27 22 40 11 00',
            'website_url' => 'https://' . $tenantSlug . '.demo.local',
            'logo_url' => 'https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=600&q=80',
            'banner_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1600&q=80',
            'primary_color' => '#d97706',
            'secondary_color' => '#111827',
            'city' => 'Abidjan',
            'country_code' => 'CI',
            'meta' => [
                'verified' => true,
                'followers' => 1280,
                'tagline' => 'Tenant de demonstration pour les tests ' . strtoupper($tenantSlug),
            ],
        ],
    );

    foreach ($events as $definition) {
        $event = Event::query()->firstOrNew(['slug' => $definition['slug']]);
        $event->fill([
            'organization_profile_id' => $organization->id,
            'category_id' => $categoryId,
            'public_status_code' => 'published',
            'title' => $definition['title'],
            'summary' => $definition['summary'],
            'description' => $definition['description'],
            'timezone' => 'UTC',
            'currency_code' => 'XOF',
            'country_code' => 'CI',
            'city_id' => 225,
            'venue_name' => $definition['venue_name'],
            'venue_address' => $definition['venue_address'],
            'cover_image_url' => $definition['cover_image_url'],
            'is_active' => true,
            'published_at' => $definition['published_at'],
            'meta' => $definition['meta'],
        ]);

        if (! $event->exists) {
            $event->public_id = (string) Str::uuid();
        }

        $event->save();

        $event->dates()->updateOrCreate(
            ['event_id' => $event->id, 'sort_order' => 0],
            [
                'starts_at' => $definition['starts_at'],
                'ends_at' => $definition['ends_at'],
                'timezone' => 'UTC',
                'is_all_day' => false,
                'meta' => [],
            ],
        );

        foreach ($definition['offers'] as $offerDefinition) {
            $offer = Offer::query()->firstOrNew([
                'offerable_type' => Event::class,
                'offerable_id' => $event->id,
                'name' => $offerDefinition['name'],
            ]);

            $offer->fill([
                'offer_type' => 'standard',
                'description' => $offerDefinition['description'],
                'price_amount' => $offerDefinition['price_amount'],
                'currency_code' => 'XOF',
                'quantity_total' => $offerDefinition['quantity_total'],
                'quantity_sold' => 0,
                'min_per_order' => 1,
                'max_per_order' => 4,
                'max_per_account' => 4,
                'sales_start_at' => $definition['published_at'],
                'sales_end_at' => $definition['starts_at'],
                'is_active' => true,
                'sort_order' => $offerDefinition['sort_order'],
                'meta' => $offerDefinition['meta'],
            ]);

            if (! $offer->exists) {
                $offer->public_id = (string) Str::uuid();
            }

            if (blank($offer->code)) {
                $offer->code = $offerCode($tenantSlug, $definition['slug'], $offerDefinition['name']);
            }

            $offer->save();
        }
    }

    echo json_encode([
        'tenant' => $tenantSlug,
        'events' => Event::query()->count(),
        'offers' => Offer::query()->count(),
        'organization_profiles' => OrganizationProfile::query()->count(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
});

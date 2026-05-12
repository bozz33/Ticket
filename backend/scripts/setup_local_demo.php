<?php

declare(strict_types=1);

use App\Models\CallForProject;
use App\Models\Category;
use App\Models\City;
use App\Models\CrowdfundingCampaign;
use App\Models\Event;
use App\Models\Offer;
use App\Models\OrganizationProfile;
use App\Models\PublicStatus;
use App\Models\Stand;
use App\Models\Tenant;
use App\Models\Training;
use App\Models\User;
use App\Support\ReferenceData\CountryReferenceImporter;
use App\Services\Tenancy\ProvisionTenant;
use Illuminate\Database\QueryException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function output(string $value): void
{
    fwrite(STDOUT, $value . PHP_EOL);
}

function upsertOffer(string $offerableType, int $offerableId, string $code, array $attributes): void
{
    Offer::query()->updateOrCreate(
        [
            'code' => $code,
        ],
        [
            'offerable_type' => $offerableType,
            'offerable_id' => $offerableId,
            ...$attributes,
        ],
    );
}

$kernel = $app->make(Kernel::class);

output('Running tenant migrations...');
try {
    $kernel->call('tenants:migrate', ['--force' => true]);
    output(trim($kernel->output()));
} catch (\Throwable $exception) {
    output('SETUP_ERROR=Database unavailable');
    output('SETUP_ERROR_DETAIL=' . $exception->getMessage());
    exit(1);
}

$tenantSlug = 'demo-front-buyer';
$tenantName = 'Demo Front Buyer';
$organizerEmail = 'organizer.demo@ticket.local';
$organizerPassword = 'TicketDemo@2026';
$buyerEmail = 'buyer.demo@ticket.local';
$buyerPassword = 'TicketBuyer@2026';
$orgLogoUrl = 'https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=600&q=80';
$orgBannerUrl = 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1600&q=80';
$eventCoverUrl = 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1600&q=80';
$eventGalleryOne = 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=1400&q=80';
$eventGalleryTwo = 'https://images.unsplash.com/photo-1515169067868-5387ec356754?auto=format&fit=crop&w=1400&q=80';
$eventGalleryThree = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1400&q=80';
$callCoverUrl = 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1600&q=80';
$callGalleryOne = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1400&q=80';
$callGalleryTwo = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=80';
$speakerOne = 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80';
$speakerTwo = 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80';
$trainingCoverUrl = 'https://images.unsplash.com/photo-1513258496099-48168024aec0?auto=format&fit=crop&w=1600&q=80';
$standCoverUrl = 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1600&q=80';
$campaignCoverUrl = 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1600&q=80';

$tenant = null;
$tenantAdmin = null;

try {
    $tenant = Tenant::query()->where('slug', $tenantSlug)->first();
} catch (QueryException $exception) {
    output('SETUP_ERROR=Database unavailable');
    output('SETUP_ERROR_DETAIL=' . $exception->getMessage());
    exit(1);
}

if ($tenant === null) {
    output('Creating demo tenant...');

    $result = $app->make(ProvisionTenant::class)->handle([
        'name' => $tenantName,
        'slug' => $tenantSlug,
        'activate' => true,
        'country_code' => 'CI',
        'currency_code' => 'XOF',
        'locale' => 'fr',
        'timezone' => 'Africa/Abidjan',
        'email' => $organizerEmail,
        'phone' => '+2250700000000',
        'description' => 'Tenant local de démonstration pour les parcours acheteur et organisateur.',
        'admin' => [
            'name' => 'Demo Organizer',
            'email' => $organizerEmail,
            'username' => 'demoorganizer',
            'password' => $organizerPassword,
            'phone' => '+2250700000001',
            'locale' => 'fr',
            'timezone' => 'Africa/Abidjan',
        ],
    ]);

    $tenant = $result['tenant'];
    $tenantAdmin = $result['tenant_admin'];
} else {
    output('Demo tenant already exists, reusing it...');
}

if (\App\Models\Country::query()->where('is_active', true)->count() === 0) {
    $referencePath = is_file(__DIR__ . '/../database/data/reference_countries_states_cities.json')
        ? __DIR__ . '/../database/data/reference_countries_states_cities.json'
        : __DIR__ . '/../database/data/reference_countries.json';

    if (is_file($referencePath)) {
        output('Importing local country and city references...');
        $referenceImport = $app->make(CountryReferenceImporter::class)->importFromFile($referencePath);
        output(sprintf('Imported %d countries and %d cities.', $referenceImport['countries'], $referenceImport['cities']));
    } else {
        output('Reference dataset not found, continuing without countries/cities bootstrap.');
    }
}

$publicStatusCode = PublicStatus::query()->where('is_active', true)->orderBy('sort_order')->value('code') ?? 'published';
$city = City::query()->orderBy('id')->first();

$tenant->run(function () use ($tenant, $publicStatusCode, $city, $buyerEmail, $buyerPassword, $orgLogoUrl, $orgBannerUrl, $eventCoverUrl, $eventGalleryOne, $eventGalleryTwo, $eventGalleryThree, $callCoverUrl, $callGalleryOne, $callGalleryTwo, $speakerOne, $speakerTwo, $trainingCoverUrl, $standCoverUrl, $campaignCoverUrl): void {
    $organizationProfile = OrganizationProfile::query()->first();

    if ($organizationProfile === null) {
        $organizationProfile = OrganizationProfile::query()->create([
            'legal_name' => 'Demo Front Buyer SARL',
            'display_name' => 'Demo Front Buyer',
            'description' => 'Organisation de démonstration pour tester le catalogue public, le checkout et le compte acheteur.',
            'email' => 'hello@demo-front-buyer.local',
            'phone' => '+2250700000002',
            'website_url' => 'https://demo-front-buyer.local',
            'logo_url' => $orgLogoUrl,
            'banner_url' => $orgBannerUrl,
            'primary_color' => '#d59a36',
            'secondary_color' => '#16b3a6',
            'city' => $city?->name ?? 'Abidjan',
            'country_code' => $tenant->country_code ?? 'CI',
            'meta' => [],
        ]);
    } else {
        $organizationProfile->forceFill([
            'legal_name' => 'Demo Front Buyer SARL',
            'display_name' => 'Demo Front Buyer',
            'description' => 'Organisation de démonstration pour tester le catalogue public, le checkout et le compte acheteur.',
            'email' => 'hello@demo-front-buyer.local',
            'phone' => '+2250700000002',
            'website_url' => 'https://demo-front-buyer.local',
            'logo_url' => $orgLogoUrl,
            'banner_url' => $orgBannerUrl,
            'city' => $city?->name ?? 'Abidjan',
            'country_code' => $tenant->country_code ?? 'CI',
        ])->save();
    }

    $eventCategory = Category::query()->where('is_active', true)->whereIn('module_scope', ['global', 'event'])->orderBy('sort_order')->orderBy('id')->first();
    $trainingCategory = Category::query()->where('is_active', true)->whereIn('module_scope', ['global', 'training'])->orderBy('sort_order')->orderBy('id')->first() ?? $eventCategory;
    $standCategory = Category::query()->where('is_active', true)->whereIn('module_scope', ['global', 'stand', 'salon'])->orderBy('sort_order')->orderBy('id')->first() ?? $eventCategory;
    $callCategory = Category::query()->where('is_active', true)->whereIn('module_scope', ['global', 'call'])->orderBy('sort_order')->orderBy('id')->first() ?? $eventCategory;
    $campaignCategory = Category::query()->where('is_active', true)->whereIn('module_scope', ['global', 'campaign'])->orderBy('sort_order')->orderBy('id')->first() ?? $eventCategory;

    $paidEvent = Event::query()->updateOrCreate(
        ['slug' => 'summit-demo-paid-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $organizationProfile->id,
            'category_id' => $eventCategory?->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Summit Demo Payant 2026',
            'summary' => 'Un événement premium payant pour valider le workflow complet côté acheteur.',
            'description' => 'Ce contenu de test permet de valider l\'affichage catalogue, la fiche détail et le checkout payant côté acheteur.',
            'timezone' => 'Africa/Abidjan',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'country_code' => $tenant->country_code ?? 'CI',
            'city_id' => $city?->id,
            'venue_name' => 'Palais des Congrès Demo',
            'venue_address' => 'Plateau, Abidjan',
            'cover_image_url' => $eventCoverUrl,
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'featured' => true,
                'popular' => true,
                'badges' => ['Payant', 'Premium'],
                'highlights' => ['Networking', 'Billetterie', 'Paiement'],
                'gallery' => [
                    $eventGalleryOne,
                    $eventGalleryTwo,
                    $eventGalleryThree,
                ],
                'program' => ['Accueil premium', 'Conférences produit', 'Sessions networking'],
                'timeline' => [
                    ['label' => 'Ouverture des portes', 'dateLabel' => '08:30', 'description' => 'Accueil et orientation des visiteurs'],
                    ['label' => 'Plénière', 'dateLabel' => '10:00', 'description' => 'Présentation des nouveautés Ticket'],
                    ['label' => 'Networking', 'dateLabel' => '17:00', 'description' => 'Rencontres et démonstrations live'],
                ],
                'faq' => [
                    ['question' => 'Billet nominatif ?', 'answer' => 'Oui, chaque billet est associé à un acheteur.'],
                    ['question' => 'Accès mobile ?', 'answer' => 'Oui, le QR code est disponible après validation.'],
                ],
                'speakers' => [
                    ['name' => 'Awa Koné', 'role' => 'Product Lead', 'imageUrl' => $speakerOne],
                    ['name' => 'Moussa Traoré', 'role' => 'Ops Manager', 'imageUrl' => $speakerTwo],
                ],
            ],
        ]
    );

    $paidEvent->dates()->delete();
    $paidEvent->dates()->create([
        'starts_at' => now()->addDays(10)->setTime(9, 0),
        'ends_at' => now()->addDays(10)->setTime(18, 0),
        'timezone' => 'Africa/Abidjan',
        'is_all_day' => false,
        'sort_order' => 1,
        'meta' => [],
    ]);

    upsertOffer(
        Event::class,
        $paidEvent->id,
        'EVENT-PAID-STD',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'ticket',
            'name' => 'Pass Standard',
            'description' => 'Accès journée complète et badge visiteur.',
            'price_amount' => 15000,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 120,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 4,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(9),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Réserver', 'perks' => ['Badge visiteur', 'Accès conférences']],
        ]
    );

    upsertOffer(
        Event::class,
        $paidEvent->id,
        'EVENT-PAID-VIP',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'ticket',
            'name' => 'Pass VIP',
            'description' => 'Accès premium, siège réservé et cocktail networking.',
            'price_amount' => 35000,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 40,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 2,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(9),
            'is_active' => true,
            'sort_order' => 2,
            'meta' => ['ctaLabel' => 'Réserver', 'perks' => ['Siège premium', 'Cocktail', 'Networking privé']],
        ]
    );

    $freeEvent = Event::query()->updateOrCreate(
        ['slug' => 'summit-demo-free-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $organizationProfile->id,
            'category_id' => $eventCategory?->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Summit Demo Gratuit 2026',
            'summary' => 'Un événement gratuit pour tester le workflow sans paiement.',
            'description' => 'Ce contenu de test permet de valider la réservation gratuite et les commandes côté acheteur.',
            'timezone' => 'Africa/Abidjan',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'country_code' => $tenant->country_code ?? 'CI',
            'city_id' => $city?->id,
            'venue_name' => 'Agora Demo',
            'venue_address' => 'Cocody, Abidjan',
            'cover_image_url' => $eventGalleryTwo,
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'featured' => false,
                'popular' => true,
                'badges' => ['Gratuit'],
                'highlights' => ['Découverte', 'Accès libre'],
                'gallery' => [$eventGalleryTwo, $eventGalleryThree],
                'program' => ['Accueil', 'Démo produit', 'Questions-réponses'],
                'timeline' => [
                    ['label' => 'Ouverture', 'dateLabel' => '10:00', 'description' => 'Accueil du public'],
                    ['label' => 'Session live', 'dateLabel' => '11:00', 'description' => 'Présentation gratuite'],
                ],
                'faq' => [
                    ['question' => 'L’entrée est-elle gratuite ?', 'answer' => 'Oui, l’inscription standard est gratuite.'],
                ],
            ],
        ]
    );

    $freeEvent->dates()->delete();
    $freeEvent->dates()->create([
        'starts_at' => now()->addDays(14)->setTime(10, 0),
        'ends_at' => now()->addDays(14)->setTime(14, 0),
        'timezone' => 'Africa/Abidjan',
        'is_all_day' => false,
        'sort_order' => 1,
        'meta' => [],
    ]);

    upsertOffer(
        Event::class,
        $freeEvent->id,
        'EVENT-FREE-STD',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'ticket',
            'name' => 'Pass Gratuit',
            'description' => 'Réservation gratuite pour test du workflow.',
            'price_amount' => 0,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 300,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 4,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(13),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Réserver', 'perks' => ['Accès libre', 'Badge digital']],
        ]
    );

    $freeCall = CallForProject::query()->updateOrCreate(
        ['slug' => 'appel-innovation-free-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $callCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Appel Innovation Gratuit 2026',
            'summary' => 'Un appel à projets gratuit de démonstration pour tester le parcours public acheteur.',
            'description' => 'Cette fiche permet de valider l\'affichage public des appels à projets, le détail et le tunnel associé à une offre de candidature.',
            'application_opens_at' => now()->subDays(2),
            'application_closes_at' => now()->addDays(21),
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $callCoverUrl,
                'gallery' => [
                    $callGalleryOne,
                    $callGalleryTwo,
                ],
                'highlights' => ['Candidatures', 'Innovation', 'Demo'],
                'badges' => ['Ouvert', 'Gratuit'],
                'conditions' => ['Être porteur d\'un projet innovant.', 'Présenter un dossier complet avant la date limite.'],
                'requiredDocuments' => ['Pitch deck', 'Présentation équipe', 'Budget prévisionnel'],
                'timeline' => [
                    ['label' => 'Ouverture', 'dateLabel' => now()->subDays(2)->format('d/m/Y'), 'description' => 'Lancement officiel de l\'appel.'],
                    ['label' => 'Clôture', 'dateLabel' => now()->addDays(21)->format('d/m/Y'), 'description' => 'Fin de réception des candidatures.'],
                ],
                'faq' => [
                    ['question' => 'Le dépôt est-il payant ?', 'answer' => 'Non, la candidature standard de démo est gratuite.'],
                ],
            ],
        ]
    );

    upsertOffer(
        CallForProject::class,
        $freeCall->id,
        'CALL-FREE',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'application',
            'name' => 'Candidature Standard',
            'description' => 'Dépôt gratuit de candidature pour test du front.',
            'price_amount' => 0,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 500,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 1,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(20),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Candidater', 'perks' => ['Dossier standard', 'Validation email']],
        ]
    );

    $paidCall = CallForProject::query()->updateOrCreate(
        ['slug' => 'appel-innovation-paid-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $callCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Appel Innovation Payant 2026',
            'summary' => 'Une candidature premium payante pour tester le checkout appliqué aux appels à projets.',
            'description' => 'Ce contenu de test permet de valider le workflow payant sur un appel à projets.',
            'application_opens_at' => now()->subDays(1),
            'application_closes_at' => now()->addDays(28),
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $callGalleryOne,
                'gallery' => [$callGalleryOne, $callGalleryTwo],
                'highlights' => ['Candidatures', 'Premium'],
                'badges' => ['Payant'],
                'conditions' => ['Dossier premium requis.', 'Sélection sur étude détaillée.'],
                'requiredDocuments' => ['Pitch deck', 'Budget', 'Références'],
            ],
        ]
    );

    upsertOffer(
        CallForProject::class,
        $paidCall->id,
        'CALL-PAID',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'application',
            'name' => 'Candidature Premium',
            'description' => 'Dépôt payant avec examen prioritaire pour test du checkout.',
            'price_amount' => 10000,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 200,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 1,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(25),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Candidater', 'perks' => ['File prioritaire', 'Accusé premium']],
        ]
    );

    $freeTraining = Training::query()->updateOrCreate(
        ['slug' => 'atelier-gratuit-demo-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $trainingCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Atelier Gratuit Demo 2026',
            'summary' => 'Une formation gratuite pour tester l’inscription sans paiement.',
            'description' => 'Atelier de démonstration destiné aux tests du workflow gratuit.',
            'timezone' => 'Africa/Abidjan',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'starts_at' => now()->addDays(7)->setTime(14, 0),
            'ends_at' => now()->addDays(7)->setTime(17, 0),
            'venue_name' => 'Campus Demo',
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $trainingCoverUrl,
                'gallery' => [$trainingCoverUrl, $eventGalleryTwo],
                'badges' => ['Gratuit'],
                'highlights' => ['Inscription', 'Atelier'],
            ],
        ]
    );

    upsertOffer(
        Training::class,
        $freeTraining->id,
        'TRAINING-FREE',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'registration',
            'name' => 'Place standard',
            'description' => 'Inscription gratuite.',
            'price_amount' => 0,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 120,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 2,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(6),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'S\'inscrire', 'perks' => ['Accès session', 'Support PDF']],
        ]
    );

    $paidTraining = Training::query()->updateOrCreate(
        ['slug' => 'masterclass-payante-demo-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $trainingCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Masterclass Payante Demo 2026',
            'summary' => 'Une formation payante pour tester le workflow complet avec paiement.',
            'description' => 'Masterclass premium destinée aux tests d’inscription payante.',
            'timezone' => 'Africa/Abidjan',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'starts_at' => now()->addDays(9)->setTime(9, 0),
            'ends_at' => now()->addDays(9)->setTime(16, 0),
            'venue_name' => 'Studio Premium Demo',
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $eventGalleryOne,
                'gallery' => [$trainingCoverUrl, $eventGalleryOne],
                'badges' => ['Payant'],
                'highlights' => ['Masterclass', 'Certification'],
            ],
        ]
    );

    upsertOffer(
        Training::class,
        $paidTraining->id,
        'TRAINING-PAID',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'registration',
            'name' => 'Pack masterclass',
            'description' => 'Inscription premium avec support et certificat.',
            'price_amount' => 25000,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 60,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 2,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(8),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'S\'inscrire', 'perks' => ['Support complet', 'Certificat']],
        ]
    );

    $freeStand = Stand::query()->updateOrCreate(
        ['slug' => 'stand-gratuit-demo-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $standCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'name' => 'Stand Gratuit Demo 2026',
            'summary' => 'Un stand gratuit pour tester une réservation sans paiement.',
            'description' => 'Stand de démonstration destiné aux tests du workflow gratuit.',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'price_amount' => 0,
            'quantity_available' => 20,
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $standCoverUrl,
                'gallery' => [$standCoverUrl, $orgBannerUrl],
                'badges' => ['Gratuit'],
                'highlights' => ['Exposition', 'Réservation'],
            ],
        ]
    );

    upsertOffer(
        Stand::class,
        $freeStand->id,
        'STAND-FREE',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'reservation',
            'name' => 'Emplacement standard',
            'description' => 'Réservation gratuite pour démonstration.',
            'price_amount' => 0,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 20,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 1,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(12),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Reserver', 'perks' => ['Zone standard']],
        ]
    );

    $paidStand = Stand::query()->updateOrCreate(
        ['slug' => 'stand-payant-demo-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $standCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'name' => 'Stand Payant Demo 2026',
            'summary' => 'Un stand premium payant pour tester le checkout.',
            'description' => 'Stand premium destiné aux tests de réservation payante.',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'price_amount' => 45000,
            'quantity_available' => 10,
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $orgBannerUrl,
                'gallery' => [$standCoverUrl, $eventGalleryOne],
                'badges' => ['Payant'],
                'highlights' => ['Premium', 'Visibilité'],
            ],
        ]
    );

    upsertOffer(
        Stand::class,
        $paidStand->id,
        'STAND-PAID',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'reservation',
            'name' => 'Emplacement premium',
            'description' => 'Réservation payante premium.',
            'price_amount' => 45000,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 10,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 1,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(12),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Reserver', 'perks' => ['Zone premium', 'Visibilité renforcée']],
        ]
    );

    $freeCampaign = CrowdfundingCampaign::query()->updateOrCreate(
        ['slug' => 'campagne-gratuite-demo-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $campaignCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Campagne Solidaire Demo 2026',
            'summary' => 'Une campagne gratuite de démonstration pour tester un flux sans paiement.',
            'description' => 'Campagne de soutien gratuite destinée aux tests de workflow.',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'target_amount' => 0,
            'raised_amount' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(20),
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $campaignCoverUrl,
                'gallery' => [$campaignCoverUrl, $orgBannerUrl],
                'badges' => ['Gratuit'],
                'highlights' => ['Soutien', 'Communauté'],
                'backers' => 0,
            ],
        ]
    );

    upsertOffer(
        CrowdfundingCampaign::class,
        $freeCampaign->id,
        'CROWD-FREE',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'contribution',
            'name' => 'Support libre',
            'description' => 'Contribution gratuite pour test de parcours.',
            'price_amount' => 0,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 1000,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 1,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(20),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Contribuer', 'perks' => ['Mention de soutien']],
        ]
    );

    $paidCampaign = CrowdfundingCampaign::query()->updateOrCreate(
        ['slug' => 'campagne-payante-demo-2026'],
        [
            'public_id' => (string) Str::uuid(),
            'category_id' => $campaignCategory?->id,
            'organization_profile_id' => $organizationProfile->id,
            'public_status_code' => $publicStatusCode,
            'title' => 'Campagne Premium Demo 2026',
            'summary' => 'Une campagne payante pour valider la contribution avec paiement.',
            'description' => 'Campagne premium destinée aux tests de contribution payante.',
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'target_amount' => 500000,
            'raised_amount' => 65000,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(30),
            'is_active' => true,
            'published_at' => now()->subHour(),
            'meta' => [
                'cover_image_url' => $eventGalleryThree,
                'gallery' => [$campaignCoverUrl, $eventGalleryThree],
                'badges' => ['Payant'],
                'highlights' => ['Contribution', 'Objectif financier'],
                'backers' => 18,
            ],
        ]
    );

    upsertOffer(
        CrowdfundingCampaign::class,
        $paidCampaign->id,
        'CROWD-PAID',
        [
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'contribution',
            'name' => 'Contribution soutien',
            'description' => 'Contribution payante standard.',
            'price_amount' => 5000,
            'currency_code' => $tenant->currency_code ?? 'XOF',
            'quantity_total' => 2000,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 3,
            'sales_start_at' => now()->subDay(),
            'sales_end_at' => now()->addDays(25),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['ctaLabel' => 'Contribuer', 'perks' => ['Remerciement', 'Accès newsletter']],
        ]
    );

    User::query()->updateOrCreate(
        ['email' => $buyerEmail],
        [
            'name' => 'Buyer Demo',
            'first_name' => 'Buyer',
            'last_name' => 'Demo',
            'username' => 'buyerdemo',
            'password' => $buyerPassword,
            'phone' => '+2250700000003',
            'locale' => 'fr',
            'timezone' => 'Africa/Abidjan',
            'email_verified_at' => now(),
            'is_active' => true,
        ]
    );
});

$tenant = Tenant::query()->where('slug', $tenantSlug)->firstOrFail();

if ($tenantAdmin === null) {
    $tenantAdmin = [
        'email' => $organizerEmail,
        'password' => $organizerPassword,
        'login_url' => url(sprintf('/tenants/%s/admin/login', $tenant->slug)),
        'access_url' => url(sprintf('/tenants/%s/admin/login', $tenant->slug)),
    ];
}

output('');
output('SETUP_OK');
output('TENANT_SLUG=' . $tenant->slug);
output('TENANT_NAME=' . $tenant->name);
output('TENANT_LOGIN_URL=' . ($tenantAdmin['login_url'] ?? url(sprintf('/tenants/%s/admin/login', $tenant->slug))));
output('TENANT_ACCESS_URL=' . ($tenantAdmin['access_url'] ?? url(sprintf('/tenants/%s/admin/login', $tenant->slug))));
output('ORGANIZER_EMAIL=' . $organizerEmail);
output('ORGANIZER_PASSWORD=' . $organizerPassword);
output('BUYER_EMAIL=' . $buyerEmail);
output('BUYER_PASSWORD=' . $buyerPassword);
output('EVENT_PAID_URL=' . 'http://127.0.0.1:3000/evenements/summit-demo-paid-2026');
output('EVENT_FREE_URL=' . 'http://127.0.0.1:3000/evenements/summit-demo-free-2026');
output('TRAINING_PAID_URL=' . 'http://127.0.0.1:3000/formations/masterclass-payante-demo-2026');
output('TRAINING_FREE_URL=' . 'http://127.0.0.1:3000/formations/atelier-gratuit-demo-2026');
output('STAND_PAID_URL=' . 'http://127.0.0.1:3000/stands/stand-payant-demo-2026');
output('STAND_FREE_URL=' . 'http://127.0.0.1:3000/stands/stand-gratuit-demo-2026');
output('CALL_PAID_URL=' . 'http://127.0.0.1:3000/appels-a-projets/appel-innovation-paid-2026');
output('CALL_FREE_URL=' . 'http://127.0.0.1:3000/appels-a-projets/appel-innovation-free-2026');
output('CROWD_PAID_URL=' . 'http://127.0.0.1:3000/crowdfunding/campagne-payante-demo-2026');
output('CROWD_FREE_URL=' . 'http://127.0.0.1:3000/crowdfunding/campagne-gratuite-demo-2026');

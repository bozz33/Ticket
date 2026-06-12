<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Models\CallForProject;
use App\Models\Category;
use App\Models\City;
use App\Models\CrowdfundingCampaign;
use App\Models\Event;
use App\Models\FormDefinition;
use App\Models\Offer;
use App\Models\OrganizationProfile;
use App\Models\Stand;
use App\Models\Tenant;
use App\Models\Training;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\Ticketing\Contracts\EventTicketOfferBridge;

class TenantDemoEventsSeeder extends Seeder
{
    /** @var array<string, array<int, int>> Category ids per module scope, reset per tenant. */
    private array $categoryCache = [];

    public function run(): void
    {
        $tenants = Tenant::query()
            ->where('status', TenantStatus::Active->value)
            ->orderBy('id')
            ->take(3)
            ->get();

        if ($tenants->isEmpty()) {
            $this->command?->warn('Aucun tenant actif trouvé.');

            return;
        }

        $cities = City::query()
            ->with('country')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(40)
            ->get();

        $perType = 10;

        foreach ($tenants as $index => $tenant) {
            $tenant->run(function () use ($tenant, $cities, $index, $perType): void {
                DB::connection('tenant')->transaction(function () use ($tenant, $cities, $index, $perType): void {
                    $this->deleteExistingDemoContent();
                    $this->categoryCache = [];
                    $profile = $this->organizationProfile($tenant);

                    // Exactly $perType items of every content type, each carrying both a free
                    // and a paid offer/ticket so every module exposes both variants.
                    for ($position = 1; $position <= $perType; $position++) {
                        $base = ($index * 50) + $position;
                        $this->createDemoEvent($tenant, $profile, $cities, $base, $position);
                        $this->createDemoTraining($tenant, $profile, $cities, $base + 1000, $position);
                        $this->createDemoStand($tenant, $profile, $cities, $base + 2000, $position);
                        $this->createDemoCallForProject($tenant, $profile, $cities, $base + 3000, $position);
                        $this->createDemoCrowdfunding($tenant, $profile, $cities, $base + 4000, $position);
                    }
                });

                $this->command?->info(sprintf('%s : %d contenus par type (5 types) créés.', $tenant->slug, $perType));
            });
        }

        // The public site reads from the central catalog projection, so it must be
        // rebuilt after seeding or the new content (and application forms) stays hidden.
        Artisan::call('ticket:rebuild-public-catalog');
        $this->command?->info('Projection catalogue public reconstruite.');
    }

    /**
     * Pick a category id of the given module scope for demo content, so catalog filters
     * are populated. Cached per tenant (reset at the start of each tenant run).
     */
    private function categoryIdForScope(string $scope, int $index): ?int
    {
        if (! array_key_exists($scope, $this->categoryCache)) {
            $this->categoryCache[$scope] = Category::query()
                ->where('module_scope', $scope)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();
        }

        $ids = $this->categoryCache[$scope];

        return $ids === [] ? null : (int) $ids[$index % count($ids)];
    }

    private function deleteExistingDemoContent(): void
    {
        foreach ([CallForProject::class, CrowdfundingCampaign::class, Stand::class, Training::class] as $modelClass) {
            $titleColumn = $modelClass === Stand::class ? 'name' : 'title';

            $modelClass::query()
                ->where(function ($query) use ($titleColumn): void {
                    $query
                        ->where('slug', 'like', 'demo-%')
                        ->orWhere('slug', 'like', '%-demo-%')
                        ->orWhere($titleColumn, 'like', '%Demo%')
                        ->orWhere($titleColumn, 'like', '%Démo%')
                        ->orWhereJsonContains('meta->seeded_demo', true);
                })
                ->with('offers')
                ->chunkById(50, function ($records) use ($modelClass): void {
                    foreach ($records as $record) {
                        if ($modelClass === CallForProject::class) {
                            FormDefinition::query()
                                ->where('owner_type', CallForProject::class)
                                ->where('owner_id', $record->getKey())
                                ->delete();
                        }

                        $record->offers()->delete();
                        $record->delete();
                    }
                });
        }

        Event::query()
            ->where(function ($query): void {
                $query
                    ->where('slug', 'like', 'demo-%')
                    ->orWhere('slug', 'like', '%-demo-%')
                    ->orWhere('title', 'like', '%Demo%')
                    ->orWhere('title', 'like', '%Démo%')
                    ->orWhereJsonContains('meta->seeded_demo', true);
            })
            ->with(['offers', 'tickets'])
            ->chunkById(50, function ($events): void {
                foreach ($events as $event) {
                    $event->offers()->delete();
                    $event->delete();
                }
            });
    }

    private function createDemoContent(Tenant $tenant, OrganizationProfile $profile, $cities, int $globalIndex, int $position): void
    {
        $modules = ['evenements', 'formations', 'stands', 'appels-a-projets', 'crowdfunding'];
        $module = $modules[($globalIndex - 1) % count($modules)];

        match ($module) {
            'formations' => $this->createDemoTraining($tenant, $profile, $cities, $globalIndex, $position),
            'stands' => $this->createDemoStand($tenant, $profile, $cities, $globalIndex, $position),
            'appels-a-projets' => $this->createDemoCallForProject($tenant, $profile, $cities, $globalIndex, $position),
            'crowdfunding' => $this->createDemoCrowdfunding($tenant, $profile, $cities, $globalIndex, $position),
            default => $this->createDemoEvent($tenant, $profile, $cities, $globalIndex, $position),
        };
    }

    private function organizationProfile(Tenant $tenant): OrganizationProfile
    {
        return OrganizationProfile::query()->firstOrCreate(
            ['email' => sprintf('demo+%s@ticket.africa', $tenant->slug)],
            [
                'legal_name' => $tenant->name,
                'display_name' => $tenant->name,
                'description' => sprintf('Organisateur démo %s pour les tests catalogue et billetterie.', $tenant->name),
                'phone' => '+2252722401100',
                'website_url' => 'https://ticket.africa',
                'primary_color' => '#d39a36',
                'secondary_color' => '#061426',
                'city' => 'Abidjan',
                'country_code' => $tenant->country_code ?: 'CI',
                'meta' => ['seeded_demo' => true],
            ],
        );
    }

    private function createDemoEvent(Tenant $tenant, OrganizationProfile $profile, $cities, int $globalIndex, int $position): void
    {
        $theme = $this->themes()[($globalIndex - 1) % count($this->themes())];
        $city = $cities->isNotEmpty() ? $cities[($globalIndex - 1) % $cities->count()] : null;
        $startsAt = Carbon::now()->addDays(7 + $globalIndex)->setTime(9 + ($globalIndex % 8), ($globalIndex % 2) * 30);
        $endsAt = (clone $startsAt)->addHours(3 + ($globalIndex % 5));
        $slug = Str::slug(sprintf('demo-%s-%03d-%s', $tenant->slug, $position, $theme['title']));

        $event = Event::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $profile->getKey(),
            'category_id' => $this->categoryIdForScope('event', $globalIndex),
            'public_status_code' => 'published',
            'title' => sprintf('%s %s', $theme['title'], $position),
            'slug' => $slug,
            'summary' => $theme['summary'],
            'description' => $theme['description'],
            'timezone' => 'Africa/Abidjan',
            'currency_code' => 'XOF',
            'country_code' => strtoupper((string) ($city?->country?->iso2 ?? $tenant->country_code ?? 'CI')),
            'city_id' => $city?->id,
            'venue_name' => $theme['venue'],
            'venue_address' => $city ? sprintf('%s, %s', $theme['venue'], $city->name) : $theme['venue'],
            'cover_image_url' => $theme['image'],
            'is_active' => true,
            'published_at' => now()->subMinutes($globalIndex),
            'meta' => [
                'seeded_demo' => true,
                'schedule' => [
                    'starts_at' => $startsAt->toDateTimeString(),
                    'ends_at' => $endsAt->toDateTimeString(),
                ],
                'badges' => $theme['badges'],
                'highlights' => $theme['highlights'],
            ],
        ]);

        $event->dates()->updateOrCreate(
            ['sort_order' => 0],
            [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'timezone' => 'Africa/Abidjan',
                'is_all_day' => false,
                'meta' => ['seeded_demo' => true],
            ],
        );

        // Every event carries both a paid and a free ticket type.
        $this->createTicket($event, $globalIndex, 1, true);
        $this->createTicket($event, $globalIndex, 2, false);
    }

    private function createDemoTraining(Tenant $tenant, OrganizationProfile $profile, $cities, int $globalIndex, int $position): void
    {
        $theme = $this->themes()[1];
        $city = $cities->isNotEmpty() ? $cities[($globalIndex - 1) % $cities->count()] : null;
        $startsAt = Carbon::now()->addDays(10 + $globalIndex)->setTime(9 + ($globalIndex % 6), 0);
        $endsAt = (clone $startsAt)->addHours(4);

        $training = Training::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $profile->getKey(),
            'category_id' => $this->categoryIdForScope('training', $globalIndex),
            'public_status_code' => 'published',
            'title' => sprintf('Formation Demo %s', $position),
            'slug' => Str::slug(sprintf('demo-%s-%03d-formation', $tenant->slug, $position)),
            'summary' => $theme['summary'],
            'description' => $theme['description'],
            'timezone' => 'Africa/Abidjan',
            'currency_code' => 'XOF',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'venue_name' => $theme['venue'],
            'is_active' => true,
            'published_at' => now()->subMinutes($globalIndex),
            'meta' => $this->baseMeta($theme, $city, $tenant, $globalIndex),
        ]);

        // Both a paid and a free enrolment offer.
        $this->createOffer($training, $globalIndex, 'Inscription formation', true);
        $this->createOffer($training, $globalIndex, 'Acces formation gratuit', false);
    }

    private function createDemoStand(Tenant $tenant, OrganizationProfile $profile, $cities, int $globalIndex, int $position): void
    {
        $theme = $this->themes()[3];
        $city = $cities->isNotEmpty() ? $cities[($globalIndex - 1) % $cities->count()] : null;
        // Alternate paid/free so the stands category always exposes both variants.
        $isPaid = $globalIndex % 2 === 0;
        $price = $isPaid ? [25000, 45000, 75000, 120000][$globalIndex % 4] : 0;

        $stand = Stand::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $profile->getKey(),
            'category_id' => $this->categoryIdForScope('stand', $globalIndex),
            'public_status_code' => 'published',
            'name' => sprintf('Stand Demo %s', $position),
            'slug' => Str::slug(sprintf('demo-%s-%03d-stand', $tenant->slug, $position)),
            'summary' => $theme['summary'],
            'description' => $theme['description'],
            'currency_code' => 'XOF',
            'price_amount' => $price,
            'quantity_available' => 20 + ($globalIndex % 18),
            'is_active' => true,
            'published_at' => now()->subMinutes($globalIndex),
            'meta' => $this->baseMeta($theme, $city, $tenant, $globalIndex),
        ]);

        // Both a paid reservation and a free discovery formula.
        $this->createOffer($stand, $globalIndex, 'Reservation stand', true, $price ?: 45000);
        $this->createOffer($stand, $globalIndex, 'Stand decouverte gratuit', false, 0);
    }

    private function createDemoCallForProject(Tenant $tenant, OrganizationProfile $profile, $cities, int $globalIndex, int $position): void
    {
        $theme = $this->callForProjectThemes()[($globalIndex - 1) % count($this->callForProjectThemes())];
        $city = $cities->isNotEmpty() ? $cities[($globalIndex - 1) % $cities->count()] : null;
        $opensAt = Carbon::now()->subDays(2);
        $closesAt = Carbon::now()->addDays(20 + $globalIndex);
        $isPaid = $globalIndex % 3 !== 0;

        $call = CallForProject::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $profile->getKey(),
            'public_status_code' => 'published',
            'title' => sprintf('%s Demo %s', $theme['title'], $position),
            'slug' => Str::slug(sprintf('demo-%s-%03d-appel-projet', $tenant->slug, $position)),
            'summary' => $theme['summary'],
            'description' => $theme['description'],
            'application_opens_at' => $opensAt,
            'application_closes_at' => $closesAt,
            'is_active' => true,
            'published_at' => now()->subMinutes($globalIndex),
            'meta' => $this->baseMeta($theme, $city, $tenant, $globalIndex) + [
                'application_mode' => $isPaid ? 'paid' : 'free',
                'application_payment' => [
                    'is_paid' => $isPaid,
                    'requires_receipt' => $isPaid,
                ],
            ],
        ]);

        $this->createOffer($call, $globalIndex, $isPaid ? 'Frais de candidature' : 'Candidature gratuite', $isPaid);
        $this->createCallForProjectApplicationForm($call, $isPaid);
    }

    /**
     * Build the application form from the tenant panel form builder (a published
     * FormDefinition owned by the call for project), so public applications render and
     * validate against the panel-built schema rather than the legacy config fallback.
     */
    private function createCallForProjectApplicationForm(CallForProject $call, bool $isPaid): void
    {
        $fields = [
            ['key' => 'full_name', 'type' => 'text', 'label' => 'Nom complet', 'required' => true, 'step' => 'identity', 'section' => 'identity', 'column_span' => 1, 'max_length' => 150],
            ['key' => 'email', 'type' => 'email', 'label' => 'Adresse e-mail', 'required' => true, 'step' => 'identity', 'section' => 'identity', 'column_span' => 1],
            ['key' => 'country_of_residence', 'type' => 'country', 'label' => 'Pays de résidence', 'required' => true, 'step' => 'identity', 'section' => 'location', 'column_span' => 1],
            ['key' => 'city_of_residence', 'type' => 'city', 'label' => 'Ville de résidence', 'required' => true, 'step' => 'identity', 'section' => 'location', 'country_field' => 'country_of_residence', 'column_span' => 1],
            ['key' => 'whatsapp_number', 'type' => 'phone', 'label' => 'Numéro WhatsApp', 'required' => true, 'step' => 'identity', 'section' => 'location', 'country_field' => 'country_of_residence', 'column_span' => 1],
            ['key' => 'organization_name', 'type' => 'text', 'label' => 'Structure / Organisation', 'required' => false, 'step' => 'project', 'section' => 'project', 'column_span' => 1],
            ['key' => 'project_title', 'type' => 'text', 'label' => 'Titre du projet', 'required' => true, 'step' => 'project', 'section' => 'project', 'column_span' => 2, 'max_length' => 180],
            ['key' => 'project_summary', 'type' => 'textarea', 'label' => 'Résumé du projet', 'required' => true, 'step' => 'project', 'section' => 'project', 'column_span' => 2, 'max_length' => 2000],
            ['key' => 'project_category', 'type' => 'radio', 'label' => 'Catégorie du projet', 'required' => true, 'step' => 'project', 'section' => 'project', 'column_span' => 2, 'options' => [
                ['value' => 'innovation', 'label' => 'Innovation & Tech'],
                ['value' => 'social', 'label' => 'Impact social'],
                ['value' => 'culture', 'label' => 'Culture & Création'],
            ]],
            ['key' => 'pitch_deck', 'type' => 'file', 'label' => 'Dossier de présentation (PDF)', 'required' => false, 'step' => 'documents', 'section' => 'documents', 'column_span' => 2, 'accept' => ['application/pdf'], 'max_size_mb' => 10],
            ['key' => 'payment_receipt', 'type' => 'file', 'label' => 'Justificatif de paiement', 'required' => $isPaid, 'visible' => $isPaid, 'step' => 'payment', 'section' => 'payment', 'column_span' => 2, 'accept' => ['application/pdf', 'image/jpeg', 'image/png'], 'max_size_mb' => 5],
            ['key' => 'consent', 'type' => 'boolean', 'label' => 'Je certifie l’exactitude des informations fournies.', 'required' => true, 'must_be_true' => true, 'step' => 'payment', 'section' => 'payment', 'column_span' => 2],
        ];

        FormDefinition::query()->updateOrCreate(
            [
                'owner_type' => CallForProject::class,
                'owner_id' => $call->getKey(),
            ],
            [
                'public_id' => (string) Str::uuid(),
                'name' => 'call-for-project-application-'.$call->getKey(),
                'title' => 'Formulaire de candidature',
                'description' => 'Renseignez les informations demandées pour soumettre votre dossier.',
                'submit_label' => 'Soumettre ma candidature',
                'success_message' => 'Votre candidature a bien été enregistrée.',
                'status' => 'published',
                'schema' => [
                    'version' => 1,
                    'steps' => [
                        ['key' => 'identity', 'title' => 'Profil', 'description' => 'Vos informations personnelles et coordonnées.'],
                        ['key' => 'project', 'title' => 'Projet', 'description' => 'Présentez votre projet.'],
                        ['key' => 'documents', 'title' => 'Documents', 'description' => 'Ajoutez les pièces du dossier.'],
                        ['key' => 'payment', 'title' => 'Validation', 'description' => 'Finalisez votre candidature.'],
                    ],
                    'fields' => $fields,
                ],
                'settings' => ['seeded_demo' => true],
            ],
        );
    }

    private function createDemoCrowdfunding(Tenant $tenant, OrganizationProfile $profile, $cities, int $globalIndex, int $position): void
    {
        $theme = $this->crowdfundingThemes()[($globalIndex - 1) % count($this->crowdfundingThemes())];
        $city = $cities->isNotEmpty() ? $cities[($globalIndex - 1) % $cities->count()] : null;
        $target = [500000, 1000000, 2500000, 5000000][$globalIndex % 4];

        $campaign = CrowdfundingCampaign::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_profile_id' => $profile->getKey(),
            'public_status_code' => 'published',
            'title' => sprintf('%s Demo %s', $theme['title'], $position),
            'slug' => Str::slug(sprintf('demo-%s-%03d-crowdfunding', $tenant->slug, $position)),
            'summary' => $theme['summary'],
            'description' => $theme['description'],
            'currency_code' => 'XOF',
            'target_amount' => $target,
            'raised_amount' => (int) floor($target * (($globalIndex % 60) / 100)),
            'starts_at' => Carbon::now()->subDays(5),
            'ends_at' => Carbon::now()->addDays(30 + ($globalIndex % 20)),
            'is_active' => true,
            'published_at' => now()->subMinutes($globalIndex),
            'meta' => $this->baseMeta($theme, $city, $tenant, $globalIndex) + [
                'backers' => 20 + ($globalIndex % 90),
            ],
        ]);

        $this->createOffer($campaign, $globalIndex, 'Contribution', true, [2000, 5000, 10000, 25000][$globalIndex % 4]);
    }

    private function createTicket(Event $event, int $globalIndex, int $ticketIndex, bool $isPaid): void
    {
        $names = ['Pass Standard', 'Pass Premium', 'Pass VIP'];
        $basePrices = [5000, 15000, 30000, 45000, 75000];
        $price = $isPaid ? $basePrices[($globalIndex + $ticketIndex) % count($basePrices)] : 0;

        $ticket = $event->tickets()->create([
            'public_id' => (string) Str::uuid(),
            'name' => $names[$ticketIndex - 1] ?? sprintf('Pass %d', $ticketIndex),
            'code' => Str::upper(Str::slug(sprintf('DEMO-%s-%d', $event->slug, $ticketIndex))),
            'description' => $price > 0
                ? 'Ticket payant de démonstration avec stock et période de vente.'
                : 'Ticket gratuit de démonstration.',
            'ticket_type' => 'standard',
            'price_amount' => $price,
            'currency_code' => $event->currency_code ?: 'XOF',
            'quantity_total' => 80 + (($globalIndex + $ticketIndex) % 8) * 25,
            'quantity_sold' => ($globalIndex + $ticketIndex) % 17,
            'quantity_reserved' => ($globalIndex + $ticketIndex) % 4,
            'min_per_order' => 1,
            'max_per_order' => $ticketIndex === 1 ? 4 : 2,
            'max_per_account' => $ticketIndex === 1 ? 6 : 2,
            'sales_start_at' => now()->subDays(2),
            'sales_end_at' => $event->dates()->first()?->starts_at?->copy()->subHours(2),
            'is_active' => true,
            'sort_order' => $ticketIndex,
            'meta' => ['seeded_demo' => true],
        ]);

        app(EventTicketOfferBridge::class)->sync($ticket->fresh(['event', 'offer', 'ticketCategory']));
    }

    private function createOffer($record, int $globalIndex, string $name, bool $isPaid, ?int $forcedPrice = null): Offer
    {
        $basePrices = [5000, 15000, 25000, 45000, 75000];
        $price = $forcedPrice ?? ($isPaid ? $basePrices[$globalIndex % count($basePrices)] : 0);

        return $record->offers()->create([
            'public_id' => (string) Str::uuid(),
            'offer_type' => 'standard',
            'name' => $name,
            'code' => Str::upper(Str::slug(sprintf('DEMO-%s-%d-%s', $record->slug, $globalIndex, $name))),
            'description' => $price > 0 ? 'Offre payante de démonstration.' : 'Offre gratuite de démonstration.',
            'price_amount' => $price,
            'currency_code' => $record->currency_code ?? 'XOF',
            'quantity_total' => 50 + ($globalIndex % 12) * 20,
            'quantity_sold' => $globalIndex % 21,
            'min_per_order' => 1,
            'max_per_order' => 4,
            'max_per_account' => 6,
            'sales_start_at' => now()->subDays(2),
            'sales_end_at' => $this->offerSalesEnd($record),
            'is_active' => true,
            'sort_order' => 1,
            'meta' => ['seeded_demo' => true, 'ctaLabel' => $price > 0 ? 'Acheter' : 'Réserver'],
        ]);
    }

    /**
     * Sales must close no later than the activity deadline (Offer model invariant),
     * so cap a default 60-day window to the offerable's date when one exists.
     */
    private function offerSalesEnd($record): Carbon
    {
        $defaultEnd = now()->addDays(60);

        $deadline = match (true) {
            $record instanceof Training => $record->starts_at,
            $record instanceof CallForProject => $record->application_closes_at,
            $record instanceof CrowdfundingCampaign => $record->ends_at,
            default => null,
        };

        return $deadline ? Carbon::parse($deadline)->min($defaultEnd) : $defaultEnd;
    }

    private function baseMeta(array $theme, ?City $city, Tenant $tenant, int $globalIndex): array
    {
        return [
            'seeded_demo' => true,
            'featured' => $globalIndex % 3 === 0,
            'popular' => $globalIndex % 4 === 0,
            'cover_image_url' => $theme['image'],
            'venue_name' => $theme['venue'] ?? null,
            'venue_address' => $city ? sprintf('%s, %s', $theme['venue'] ?? 'Lieu principal', $city->name) : ($theme['venue'] ?? null),
            'city' => $city?->name ?? 'Abidjan',
            'country_code' => strtoupper((string) ($city?->country?->iso2 ?? $tenant->country_code ?? 'CI')),
            'badges' => $theme['badges'],
            'highlights' => $theme['highlights'],
        ];
    }

    private function themes(): array
    {
        return [
            [
                'title' => 'Forum Innovation Afrique',
                'summary' => 'Rencontre professionnelle autour de la tech, des startups et du financement.',
                'description' => 'Conférences, networking et ateliers pratiques pour décideurs, fondateurs et partenaires institutionnels.',
                'venue' => 'Palais des Congrès',
                'image' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Networking', 'Innovation'],
                'highlights' => ['Panels experts', 'Rencontres investisseurs', 'Ateliers terrain'],
            ],
            [
                'title' => 'Masterclass Marketing Digital',
                'summary' => 'Formation intensive pour structurer l’acquisition et la conversion digitale.',
                'description' => 'Une journée pratique avec cas concrets, outils, templates et session questions-réponses.',
                'venue' => 'Centre de formation business',
                'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Formation', 'Pratique'],
                'highlights' => ['Templates inclus', 'Coaching en direct', 'Cas réels'],
            ],
            [
                'title' => 'Concert Live Experience',
                'summary' => 'Soirée musicale avec artistes locaux, DJ set et espace premium.',
                'description' => 'Une expérience live pensée pour un public large, avec accès standard et zones premium.',
                'venue' => 'Esplanade culturelle',
                'image' => 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Live', 'Musique'],
                'highlights' => ['Scène principale', 'Espace premium', 'Food court'],
            ],
            [
                'title' => 'Salon Business & Invest',
                'summary' => 'Salon B2B pour exposants, acheteurs, PME et partenaires financiers.',
                'description' => 'Stands, rendez-vous qualifiés, démonstrations et conférences métiers.',
                'venue' => 'Parc des expositions',
                'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['B2B', 'Investissement'],
                'highlights' => ['Stands exposants', 'Rendez-vous B2B', 'Pitchs PME'],
            ],
            [
                'title' => 'Atelier Leadership Jeunes',
                'summary' => 'Atelier communautaire pour développer leadership, impact et prise de parole.',
                'description' => 'Sessions collaboratives, exercices pratiques et mentorat autour de projets citoyens.',
                'venue' => 'Maison des jeunes',
                'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Communauté', 'Leadership'],
                'highlights' => ['Mentorat', 'Travail en groupe', 'Pitch final'],
            ],
        ];
    }

    private function callForProjectThemes(): array
    {
        return [
            [
                'title' => 'Appel à projets Innovation',
                'summary' => 'Sélection de projets à impact pour accompagnement, visibilité et financement.',
                'description' => 'Les candidats soumettent un dossier structuré avant présélection par le comité.',
                'venue' => 'Programme incubateur',
                'image' => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Candidature', 'Impact'],
                'highlights' => ['Dossier en ligne', 'Mentorat', 'Jury expert'],
            ],
            [
                'title' => 'Challenge Entrepreneurs',
                'summary' => 'Concours de solutions entrepreneuriales avec pitch final et accompagnement.',
                'description' => 'Un parcours par étapes pour repérer et accélérer les meilleurs projets.',
                'venue' => 'Campus innovation',
                'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Pitch', 'Sélection'],
                'highlights' => ['Formulaire dédié', 'Shortlist', 'Prix final'],
            ],
        ];
    }

    private function crowdfundingThemes(): array
    {
        return [
            [
                'title' => 'Campagne Solidarité',
                'summary' => 'Collecte publique pour financer une action communautaire vérifiée.',
                'description' => 'Chaque contribution soutient une initiative locale avec suivi transparent.',
                'venue' => 'Campagne en ligne',
                'image' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Contribution', 'Communauté'],
                'highlights' => ['Montant libre', 'Suivi public', 'Impact local'],
            ],
            [
                'title' => 'Projet Culturel Participatif',
                'summary' => 'Financement participatif pour produire un événement ou une initiative culturelle.',
                'description' => 'Les contributeurs soutiennent la réalisation du projet sans billetterie classique.',
                'venue' => 'Collecte publique',
                'image' => 'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1400&q=80',
                'badges' => ['Culture', 'Soutien'],
                'highlights' => ['Objectif clair', 'Paliers', 'Contribution libre'],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AccessPassStatus;
use App\Enums\OrderStatus;
use App\Enums\TenantStatus;
use App\Models\AccessPass;
use App\Models\AccessPassScan;
use App\Models\CallForProject;
use App\Models\CallForProjectSubmission;
use App\Models\ContentLike;
use App\Models\CrowdfundingCampaign;
use App\Models\CrowdfundingContribution;
use App\Models\Event;
use App\Models\EventLike;
use App\Models\MobileDevice;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrganizationFollower;
use App\Models\OrganizationProfile;
use App\Models\PlatformSupportTicket;
use App\Models\Receipt;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\AccessPassCheckinService;
use App\Support\Microservices\DomainEventBridge;
use App\Support\References\ReferenceGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ticket\Payments\Application\OrderFulfillmentService;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;

/**
 * Seeds realistic buyer activity on top of the demo content: buyers, fulfilled orders
 * (real OrderFulfillmentService -> order + numbered receipt + access passes), and gate
 * check-ins (real AccessPassCheckinService). Runs after TenantDemoEventsSeeder.
 *
 * Paid checkout normally goes through the gateway; here we drive the same server-side
 * fulfilment the webhook triggers, with a permissive checkout-item resolver, so the demo
 * gets genuine order/receipt/pass rows without an external payment call.
 */
class TenantDemoActivitySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()
            ->where('status', TenantStatus::Active->value)
            ->orderBy('id')
            ->take(3)
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant): void {
                $this->wipeActivity();

                $buyers = $this->makeBuyers(6);
                $scanner = $buyers->first();

                $offers = Offer::query()
                    ->where('price_amount', '>', 0)
                    ->inRandomOrder()
                    ->limit(18)
                    ->get();

                $service = $this->makeFulfillmentService();
                $orders = collect();

                foreach ($offers as $i => $offer) {
                    $buyer = $buyers[$i % $buyers->count()];
                    $quantity = ($i % 3) + 1;
                    $ref = 'DEMO-TX-'.Str::upper(Str::random(10));

                    $order = $service->fulfill($ref, $this->payload($ref, $offer, $buyer, $quantity));

                    if ($order instanceof Order) {
                        $orders->push($order);
                    }
                }

                $this->checkInSomePasses($scanner);
                $this->requestRefunds($orders->take(4));
                $this->seedContributions($buyers);
                $this->seedSubmissions($buyers);
                $this->seedEngagement($buyers);
                $this->seedDevices($buyers);

                $this->command?->info(sprintf(
                    '%s : %d commandes, %d reçus, %d passes, %d scans, %d remb., %d contrib., %d candid., %d likes, %d devices.',
                    $tenant->slug,
                    Order::count(),
                    Receipt::count(),
                    AccessPass::count(),
                    AccessPassScan::count(),
                    Order::where('status', OrderStatus::RefundPending->value)->count(),
                    CrowdfundingContribution::count(),
                    CallForProjectSubmission::count(),
                    EventLike::count(),
                    MobileDevice::count(),
                ));
            });
        }

        $this->seedCentralFinanceAndSupport($tenants);
    }

    private function requestRefunds(Collection $orders): void
    {
        foreach ($orders->values() as $i => $order) {
            $meta = (array) ($order->meta ?? []);
            $meta['refund_request'] = [
                'reason_code' => ['customer_request', 'event_cancelled', 'duplicate_order'][$i % 3],
                'reason' => 'Demande de remboursement de démonstration.',
                'requested_at' => now()->subDays($i + 1)->toIso8601String(),
                'status' => 'pending_tenant_review',
            ];

            $order->forceFill([
                'status' => OrderStatus::RefundPending,
                'meta' => $meta,
            ])->save();
        }
    }

    private function seedContributions(Collection $buyers): void
    {
        $campaigns = CrowdfundingCampaign::query()->with('offers')->limit(10)->get();

        foreach ($campaigns as $i => $campaign) {
            $offer = $campaign->offers->first();
            $count = 3 + ($i % 4);

            for ($n = 0; $n < $count; $n++) {
                $buyer = $buyers[($i + $n) % $buyers->count()];

                CrowdfundingContribution::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'crowdfunding_campaign_id' => $campaign->getKey(),
                    'offer_id' => $offer?->getKey(),
                    'buyer_user_id' => $buyer->getKey(),
                    'transaction_reference' => 'DEMO-CTR-'.Str::upper(Str::random(8)),
                    'contributor_name' => $buyer->name,
                    'contributor_email' => $buyer->email,
                    'amount' => [2000, 5000, 10000, 25000][($i + $n) % 4],
                    'currency_code' => 'XOF',
                    'status' => 'confirmed',
                    'is_anonymous' => ($n % 4) === 0,
                    'paid_at' => now()->subDays($n + 1),
                    'meta' => ['seeded_demo' => true],
                ]);
            }
        }
    }

    private function seedSubmissions(Collection $buyers): void
    {
        $calls = CallForProject::query()->limit(10)->get();

        foreach ($calls as $i => $call) {
            $count = 2 + ($i % 4);

            for ($n = 0; $n < $count; $n++) {
                $buyer = $buyers[($i + $n) % $buyers->count()];

                CallForProjectSubmission::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'call_for_project_id' => $call->getKey(),
                    'status' => 'submitted',
                    'applicant_name' => $buyer->name,
                    'applicant_email' => $buyer->email,
                    'phone_country_code' => 'CI',
                    'phone_number' => '+225070000'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                    'country_code' => 'CI',
                    'city_name' => 'Abidjan',
                    'answers' => [
                        'project_title' => 'Projet démo '.($n + 1),
                        'project_summary' => 'Résumé de démonstration du projet candidat.',
                    ],
                    'files' => [],
                    'submitted_at' => now()->subDays($n + 1),
                    'meta' => ['seeded_demo' => true],
                ]);
            }
        }
    }

    private function seedEngagement(Collection $buyers): void
    {
        foreach (Event::query()->limit(10)->get() as $i => $event) {
            foreach ($buyers->take(3 + ($i % 3)) as $buyer) {
                EventLike::query()->firstOrCreate([
                    'event_id' => $event->getKey(),
                    'user_id' => $buyer->getKey(),
                ]);

                ContentLike::query()->firstOrCreate(
                    ['module' => 'evenements', 'content_slug' => $event->slug, 'user_id' => $buyer->getKey()],
                    ['content_public_id' => $event->public_id],
                );
            }
        }

        foreach (OrganizationProfile::query()->limit(5)->get() as $profile) {
            foreach ($buyers as $buyer) {
                OrganizationFollower::query()->firstOrCreate([
                    'organization_profile_id' => $profile->getKey(),
                    'user_id' => $buyer->getKey(),
                ]);
            }
        }
    }

    private function seedDevices(Collection $buyers): void
    {
        foreach ($buyers->values() as $i => $buyer) {
            MobileDevice::query()->updateOrCreate(
                ['device_id' => 'demo-device-'.$buyer->getKey()],
                [
                    'user_id' => $buyer->getKey(),
                    'platform' => $i % 2 === 0 ? 'android' : 'ios',
                    'push_provider' => 'fcm',
                    'push_token' => 'demo-token-'.Str::random(24),
                    'app_version' => '1.0.0',
                    'device_name' => 'Demo Device '.($i + 1),
                    'locale' => 'fr',
                    'timezone' => 'Africa/Abidjan',
                    'last_seen_at' => now()->subHours($i),
                    'meta' => ['seeded_demo' => true],
                ],
            );
        }
    }

    private function seedCentralFinanceAndSupport(Collection $tenants): void
    {
        foreach ($tenants->values() as $i => $tenant) {
            Settlement::query()->updateOrCreate(
                ['reference' => 'DEMO-STL-'.Str::upper($tenant->slug)],
                [
                    'tenant_id' => $tenant->getKey(),
                    'status' => ['pending', 'scheduled', 'paid'][$i % 3],
                    'period_start' => now()->startOfMonth(),
                    'period_end' => now()->endOfMonth(),
                    'gross_amount' => 1500000,
                    'fee_amount' => 75000,
                    'reserve_amount' => 0,
                    'payout_fee_amount' => 5000,
                    'net_amount' => 1420000,
                    'currency_code' => 'XOF',
                    'scheduled_at' => now()->addDays(3),
                    'meta' => ['seeded_demo' => true],
                ],
            );

            PlatformSupportTicket::query()->updateOrCreate(
                ['reference' => 'DEMO-SUP-'.Str::upper($tenant->slug)],
                [
                    'tenant_id' => $tenant->getKey(),
                    'subject' => 'Demande de support de démonstration',
                    'requester_name' => $tenant->name,
                    'requester_email' => 'demo+'.$tenant->slug.'@ticket.africa',
                    'status' => ['open', 'pending', 'resolved'][$i % 3],
                    'priority' => ['low', 'normal', 'high'][$i % 3],
                    'category' => 'general',
                    'opened_at' => now()->subDays($i + 1),
                    'last_activity_at' => now()->subHours($i),
                    'meta' => ['seeded_demo' => true],
                ],
            );
        }
    }

    private function wipeActivity(): void
    {
        // Hard delete in foreign-key-safe order (children before parents).
        foreach ([
            'access_pass_scans',
            'access_passes',
            'receipts',
            'crowdfunding_contributions',
            'orders',
            'call_for_project_submissions',
            'event_likes',
            'content_likes',
            'organization_followers',
            'mobile_devices',
        ] as $table) {
            DB::connection('tenant')->table($table)->delete();
        }
    }

    private function makeBuyers(int $count): Collection
    {
        return collect(range(1, $count))->map(function (int $n): User {
            return User::query()->updateOrCreate(
                ['email' => sprintf('demo.buyer%d@ticket.africa', $n)],
                [
                    'name' => sprintf('Acheteur Démo %d', $n),
                    'first_name' => 'Acheteur',
                    'last_name' => (string) $n,
                    'password' => 'password123',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        });
    }

    private function checkInSomePasses(User $scanner): void
    {
        $passes = AccessPass::query()
            ->where('status', AccessPassStatus::Active->value)
            ->limit(8)
            ->get();

        $request = Request::create('/demo/checkin', 'POST', [], [], [], [
            'HTTP_X_TERMINAL_ID' => 'demo-gate',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->attributes->set('tenant_user', $scanner);

        $service = app(AccessPassCheckinService::class);

        foreach ($passes as $pass) {
            $service->consume($pass->fresh(), $request);
        }
    }

    private function payload(string $ref, Offer $offer, User $buyer, int $quantity): array
    {
        return [
            'data' => [
                'reference' => $ref,
                'status' => 'success',
                'amount' => $offer->price_amount * $quantity,
                'currency' => $offer->currency_code ?: 'XOF',
                'fees' => 0,
                'metadata' => [
                    'offer_id' => $offer->getKey(),
                    'quantity' => $quantity,
                    'buyer_user_id' => $buyer->getKey(),
                    'buyer_name' => $buyer->name,
                    'buyer_email' => $buyer->email,
                ],
            ],
        ];
    }

    private function makeFulfillmentService(): OrderFulfillmentService
    {
        $resolver = new class implements CheckoutItemResolver
        {
            public function resolve(string $identifier, ?string $type = null): ?CheckoutItem
            {
                return null;
            }

            public function quantityBounds(CheckoutItem $item): array
            {
                return ['min' => 1, 'max' => 100];
            }

            public function reserve(CheckoutItem $item, int $quantity, array $context = []): ?CheckoutReservation
            {
                return null;
            }

            public function release(array $checkout): bool
            {
                return true;
            }

            public function confirm(array $checkout, int $quantity): bool
            {
                return false;
            }
        };

        return new OrderFulfillmentService(
            new ReferenceGenerator,
            $resolver,
            app(DomainEventBridge::class),
        );
    }
}

<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Support\ReferenceData\CountryReferenceImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Ticket\Notifications\Contracts\OutboxDispatcher;
use Ticket\PublicCatalog\Application\PublicCatalogProjector;
use Ticket\Ticketing\Contracts\EventTicketInventory;
use Ticket\Ticketing\Contracts\EventTicketOfferBridge;

$resolveMigrationPaths = function (string $scope): array {
    $configuration = config("ticket.migration_paths.{$scope}", []);
    $paths = collect($configuration['directories'] ?? [])
        ->merge($configuration['files'] ?? [])
        ->merge(collect($configuration['globs'] ?? [])->flatMap(fn (string $pattern) => glob($pattern) ?: []))
        ->filter(fn (string $path) => file_exists($path))
        ->unique()
        ->values()
        ->all();

    sort($paths);

    return $paths;
};

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ticket:migration-paths {scope : central|tenant}', function (string $scope) use ($resolveMigrationPaths) {
    $paths = $resolveMigrationPaths($scope);

    foreach ($paths as $path) {
        $this->line($path);
    }
})->purpose('Display resolved migration paths for central or tenant scope');

Artisan::command('ticket:migrate-central', function () use ($resolveMigrationPaths) {
    $paths = $resolveMigrationPaths('central');

    return Artisan::call('migrate', [
        '--database' => config('ticket.central_connection', 'central'),
        '--path' => $paths,
        '--realpath' => true,
        '--force' => true,
    ]);
})->purpose('Run central database migrations using explicit paths');

Artisan::command('ticket:migrate-tenant', function () use ($resolveMigrationPaths) {
    $paths = $resolveMigrationPaths('tenant');

    return Artisan::call('migrate', [
        '--database' => config('ticket.tenant_connection', 'tenant'),
        '--path' => $paths,
        '--realpath' => true,
        '--force' => true,
    ]);
})->purpose('Run tenant database migrations using explicit paths');

Artisan::command('ticket:import-reference-countries {path?}', function (?string $path = null) {
    $resolvedPath = $path
        ? (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path) ? $path : base_path($path))
        : (is_file(base_path('database/data/reference_countries_states_cities.json'))
            ? base_path('database/data/reference_countries_states_cities.json')
            : base_path('database/data/reference_countries.json'));

    $result = app(CountryReferenceImporter::class)->importFromFile($resolvedPath);

    $this->info(sprintf(
        'Reference import completed: %d countries processed, %d cities processed. Active totals: %d countries, %d cities.',
        $result['countries'],
        $result['cities'],
        $result['active_countries_total'],
        $result['active_cities_total'],
    ));
})->purpose('Import local country and city reference data into the central database');

Artisan::command('ticket:rebuild-public-catalog {tenant? : Tenant slug or public id}', function (?string $tenant = null): int {
    $projector = app(PublicCatalogProjector::class);

    if (is_string($tenant) && trim($tenant) !== '') {
        $resolvedTenant = Tenant::query()
            ->where('slug', $tenant)
            ->orWhere('public_id', $tenant)
            ->first();

        if (! $resolvedTenant) {
            $this->error(sprintf('Tenant [%s] not found.', $tenant));

            return 1;
        }

        $count = $projector->rebuildTenant($resolvedTenant);
        $this->info(sprintf('Public catalog projection rebuilt for %s: %d item(s).', $resolvedTenant->slug, $count));

        return 0;
    }

    $summary = $projector->rebuildAll();
    $this->info(sprintf(
        'Public catalog projection rebuilt: %d tenant(s), %d item(s).',
        $summary['tenants'],
        $summary['items'],
    ));

    return 0;
})->purpose('Rebuild the central public catalog read model');

Artisan::command('ticket:backfill-event-tickets', function (): int {
    $summary = app(EventTicketOfferBridge::class)->backfillFromEventOffers();

    $this->info(sprintf(
        'Event ticket backfill completed: %d created, %d already linked, %d skipped.',
        $summary['created'],
        $summary['linked'],
        $summary['skipped'],
    ));

    return 0;
})->purpose('Create dedicated event tickets from legacy event offers in the current tenant database');

Artisan::command('ticket:release-expired-ticket-reservations {--tenant= : Tenant slug or public id} {--all-tenants : Process all active tenants} {--minutes=20 : Reservation age before release} {--limit=100 : Maximum reservations to inspect per tenant}', function (): int {
    $minutes = max(1, (int) $this->option('minutes'));
    $limit = max(1, (int) $this->option('limit'));
    $tenantIdentifier = trim((string) $this->option('tenant'));

    $release = function (?Tenant $tenant = null) use ($minutes, $limit): array {
        $summary = app(EventTicketInventory::class)->releaseExpiredReservations($minutes, $limit);

        $this->info(sprintf(
            '%sExpired ticket reservation release completed: %d processed, %d released, %d skipped.',
            $tenant ? "[{$tenant->slug}] " : '',
            $summary['processed'],
            $summary['released'],
            $summary['skipped'],
        ));

        return $summary;
    };

    if ($tenantIdentifier !== '') {
        $tenant = Tenant::query()
            ->where('slug', $tenantIdentifier)
            ->orWhere('public_id', $tenantIdentifier)
            ->first();

        if (! $tenant instanceof Tenant) {
            $this->error(sprintf('Tenant [%s] not found.', $tenantIdentifier));

            return 1;
        }

        $tenant->run(fn () => $release($tenant));

        return 0;
    }

    if ((bool) $this->option('all-tenants')) {
        $totals = ['processed' => 0, 'released' => 0, 'skipped' => 0, 'tenants' => 0];

        Tenant::query()
            ->where('status', TenantStatus::Active)
            ->orderBy('id')
            ->get()
            ->each(function (Tenant $tenant) use (&$totals, $release): void {
                $summary = $tenant->run(fn () => $release($tenant));

                $totals['tenants']++;
                $totals['processed'] += (int) $summary['processed'];
                $totals['released'] += (int) $summary['released'];
                $totals['skipped'] += (int) $summary['skipped'];
            });

        $this->info(sprintf(
            'All tenants completed: %d tenants, %d processed, %d released, %d skipped.',
            $totals['tenants'],
            $totals['processed'],
            $totals['released'],
            $totals['skipped'],
        ));

        return 0;
    }

    $release();

    return 0;
})->purpose('Release expired pending reservations for event tickets');

Artisan::command('ticket:dispatch-outbox {--limit=100 : Maximum messages to dispatch}', function (): int {
    $summary = app(OutboxDispatcher::class)->dispatchPending((int) $this->option('limit'));

    $this->info(sprintf(
        'Outbox dispatch completed: %d processed, %d failed.',
        $summary['processed'],
        $summary['failed'],
    ));

    return $summary['failed'] > 0 ? 1 : 0;
})->purpose('Dispatch pending domain outbox messages');

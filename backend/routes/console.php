<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Support\Microservices\MicroserviceClientFactory;
use App\Support\Microservices\MicroserviceRegistry;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Ticket\Notifications\Contracts\OutboxDispatcher;
use Ticket\PublicCatalog\Application\PublicCatalogProjector;
use Ticket\ReferenceData\Contracts\CountryReferenceImport;
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

    $result = app(CountryReferenceImport::class)->importFromFile($resolvedPath);

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

Artisan::command('ticket:outbox-stats', function (): int {
    $stats = app(OutboxDispatcher::class)->stats();

    $rows = collect(['pending', 'processing', 'published', 'failed'])
        ->map(fn (string $status): array => [$status, $stats[$status] ?? 0])
        ->all();

    $this->table(['Status', 'Total'], $rows);

    return 0;
})->purpose('Display domain outbox message counts by status');

Artisan::command('ticket:retry-outbox {--limit=100 : Maximum failed messages to retry} {--delay=60 : Delay in seconds before messages become available}', function (): int {
    $summary = app(OutboxDispatcher::class)->retryFailed((int) $this->option('limit'), (int) $this->option('delay'));

    $this->info(sprintf(
        'Outbox retry scheduled: %d message(s), delay %d second(s).',
        $summary['retried'],
        $summary['delay_seconds'],
    ));

    return 0;
})->purpose('Move failed domain outbox messages back to pending with a delay');

Artisan::command('ticket:sync-front-menus', function (): int {
    $connection = DB::connection('central');
    $now = now();
    $menus = [
        [
            'key' => 'header_top_left',
            'title' => 'Header top gauche',
            'location' => 'header_top_left',
            'items' => [
                ['label' => 'support@ticket.africa', 'href' => 'mailto:support@ticket.africa', 'icon' => 'mail'],
                ['label' => '+225 27 22 40 11 00', 'href' => 'tel:+2252722401100', 'icon' => 'phone'],
                ['label' => 'Disponible 24h/24', 'href' => '#availability', 'icon' => 'status'],
            ],
        ],
        [
            'key' => 'header_top_right',
            'title' => 'Header top droite',
            'location' => 'header_top_right',
            'items' => [
                ['label' => 'CGV & remboursements', 'href' => '/remboursement'],
                ['label' => 'FAQ', 'href' => '/faq'],
                ['label' => 'Mentions légales', 'href' => '/mentions-legales'],
                ['label' => 'Paiement sécurisé', 'href' => '#secure-payment', 'icon' => 'lock'],
            ],
        ],
        [
            'key' => 'header_actions',
            'title' => 'Header actions',
            'location' => 'header_actions',
            'items' => [
                ['label' => 'Mon compte', 'href' => '/compte', 'icon' => 'user'],
                ['label' => 'Devenir organisateur', 'href' => '/devenir-organisateur', 'icon' => 'organizer'],
            ],
        ],
    ];

    foreach ($menus as $menu) {
        $connection->table('front_menus')->updateOrInsert(
            ['key' => $menu['key']],
            [
                'title' => $menu['title'],
                'location' => $menu['location'],
                'is_active' => true,
                'settings' => json_encode([], JSON_THROW_ON_ERROR),
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $menuId = $connection->table('front_menus')->where('key', $menu['key'])->value('id');

        foreach ($menu['items'] as $index => $item) {
            $connection->table('front_menu_items')->updateOrInsert(
                ['front_menu_id' => $menuId, 'href' => $item['href']],
                [
                    'label' => $item['label'],
                    'target' => '_self',
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'meta' => json_encode(array_filter(['icon' => $item['icon'] ?? null]), JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    $this->info('Front menu zones synchronized.');

    return 0;
})->purpose('Create explicit front header menu zones with default icons without deleting existing menus');

Artisan::command('ticket:production-check', function (): int {
    $failures = 0;

    $check = function (string $label, bool $passes, string $details = '') use (&$failures): void {
        if ($passes) {
            $this->info(sprintf('[OK] %s%s', $label, $details !== '' ? " — {$details}" : ''));

            return;
        }

        $failures++;
        $this->error(sprintf('[FAIL] %s%s', $label, $details !== '' ? " — {$details}" : ''));
    };

    $warn = function (string $label, bool $passes, string $details = ''): void {
        if ($passes) {
            $this->info(sprintf('[OK] %s%s', $label, $details !== '' ? " — {$details}" : ''));

            return;
        }

        $this->warn(sprintf('[WARN] %s%s', $label, $details !== '' ? " — {$details}" : ''));
    };

    $check('APP_ENV production', app()->environment('production'), sprintf('current=%s', app()->environment()));
    $check('APP_DEBUG disabled', config('app.debug') === false, sprintf('current=%s', config('app.debug') ? 'true' : 'false'));
    $check('APP_KEY configured', filled(config('app.key')));
    $check('APP_URL configured', filled(config('app.url')));
    $check('PUBLIC_FRONTEND_URL configured', filled(config('ticket.public_frontend_url')));

    $check('Central DB reachable', rescue(fn (): bool => DB::connection(config('ticket.central_connection', 'central'))->select('select 1') !== [], false));
    $warn('Tenant DB reachable', rescue(fn (): bool => DB::connection(config('ticket.tenant_connection', 'tenant'))->select('select 1') !== [], false));
    $warn('Platform settings table exists', rescue(fn (): bool => Schema::connection(config('ticket.central_connection', 'central'))->hasTable('platform_settings'), false));

    $check('Storage directory writable', File::isWritable(storage_path()));
    $check('Logs directory writable', File::isWritable(storage_path('logs')));
    $check('Public storage linked or available', File::exists(public_path('storage')) || File::exists(storage_path('app/public')));

    $check('Queue driver not sync', config('queue.default') !== 'sync', sprintf('current=%s', config('queue.default')));
    $check('Cache driver not array', config('cache.default') !== 'array', sprintf('current=%s', config('cache.default')));
    $warn('Session secure cookies enabled', (bool) config('session.secure'), sprintf('current=%s', config('session.secure') ? 'true' : 'false'));

    $mailMailer = (string) config('mail.default');
    $warn('Mail transport configured', ! in_array($mailMailer, ['array', 'log'], true), sprintf('current=%s', $mailMailer));
    $warn('Mail sender address configured', filled(config('mail.from.address')));

    $allowedOrigins = (array) config('cors.allowed_origins', []);
    $check('CORS origins configured', count($allowedOrigins) > 0);
    $warn('CORS does not allow wildcard origin', ! in_array('*', $allowedOrigins, true));

    $warn('Backups path configured or present', filled(env('BACKUP_PATH')) || File::exists(storage_path('app/backups')));
    $warn('Payments log channel configured', filled(config('ticket.logging.payments_channel')));
    $warn('Security log channel configured', filled(config('ticket.logging.security_channel')));

    if ($failures > 0) {
        $this->error(sprintf('Production check completed with %d blocking failure(s).', $failures));

        return 1;
    }

    $this->info('Production check completed without blocking failures.');

    return 0;
})->purpose('Validate key production readiness settings without mutating data');

Artisan::command('ticket:microservices-check {--only= : Check one configured microservice name} {--tenant= : Tenant id/header value to send}', function (): int {
    $registry = app(MicroserviceRegistry::class);
    $clientFactory = app(MicroserviceClientFactory::class);
    $only = trim((string) $this->option('only'));
    $tenantId = trim((string) $this->option('tenant')) ?: null;
    $failures = 0;
    $rows = [];

    $services = collect($registry->names())
        ->when($only !== '', fn ($services) => $services->filter(fn (string $service): bool => $service === $only))
        ->values();

    if ($only !== '' && $services->isEmpty()) {
        $this->error(sprintf('Unknown microservice [%s].', $only));

        return 1;
    }

    foreach ($services as $service) {
        $enabled = $registry->isEnabled($service);
        $baseUrl = $registry->baseUrl($service);
        $status = 'disabled';
        $details = 'not checked';

        if ($baseUrl === '') {
            $status = 'missing-url';
            $details = 'base URL is empty';
            $failures++;
        } elseif ($enabled) {
            try {
                $response = $clientFactory
                    ->for($service, $tenantId)
                    ->get('/health');

                $status = $response->successful() ? 'ok' : 'failed';
                $details = sprintf('HTTP %d', $response->status());

                if (! $response->successful()) {
                    $failures++;
                }
            } catch (Throwable $exception) {
                $status = 'failed';
                $details = $exception->getMessage();
                $failures++;
            }
        }

        $rows[] = [
            'service' => $service,
            'enabled' => $enabled ? 'yes' : 'no',
            'url' => $baseUrl,
            'health' => $status,
            'details' => $details,
        ];
    }

    $this->table(['Service', 'Enabled', 'Base URL', 'Health', 'Details'], $rows);

    if ($failures > 0) {
        $this->error(sprintf('Microservices check completed with %d failure(s).', $failures));

        return 1;
    }

    $this->info('Microservices check completed without failures.');

    return 0;
})->purpose('Check configured microservice URLs and /health endpoints when enabled');

Artisan::command('ticket:resource-audit', function (): int {
    $resourceFiles = collect(File::allFiles(app_path('Filament')))
        ->filter(fn (SplFileInfo $file): bool => str_ends_with($file->getFilename(), 'Resource.php'))
        ->values();

    $sourceFiles = collect(array_merge(
        File::allFiles(app_path()),
        File::exists(base_path('packages')) ? File::allFiles(base_path('packages')) : [],
        File::allFiles(base_path('routes')),
    ))->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')->values();

    $rows = $resourceFiles->map(function (SplFileInfo $file) use ($sourceFiles): array {
        $path = $file->getRealPath();
        $contents = File::get($path);
        $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

        preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatch);
        preg_match('/class\s+([A-Za-z0-9_]+)/', $contents, $classMatch);
        preg_match('/protected static \?string \$model = ([^;]+)::class;/', $contents, $modelMatch);

        $resourceClass = trim(($namespaceMatch[1] ?? '').'\\'.($classMatch[1] ?? ''));
        $modelBase = $modelMatch[1] ?? null;
        $modelClass = null;
        $table = '—';
        $rowCount = 'n/a';
        $usageCount = 0;
        $recommendation = 'review';

        if ($modelBase !== null) {
            preg_match('/use\s+([^;]*\\\\'.preg_quote($modelBase, '/').');/', $contents, $useMatch);
            $modelClass = $useMatch[1] ?? 'App\\Models\\'.$modelBase;
        }

        if ($modelClass !== null && class_exists($modelClass)) {
            try {
                $model = new $modelClass;
                $table = $model->getTable();
                $connection = $model->getConnectionName() ?: config('database.default');
                $rowCount = Schema::connection($connection)->hasTable($table)
                    ? (string) DB::connection($connection)->table($table)->count()
                    : 'missing';
            } catch (Throwable) {
                $rowCount = 'error';
            }

            $modelShort = class_basename($modelClass);
            $usageCount = $sourceFiles
                ->reject(fn (SplFileInfo $sourceFile): bool => $sourceFile->getRealPath() === $path)
                ->filter(fn (SplFileInfo $sourceFile): bool => str_contains(File::get($sourceFile->getRealPath()), $modelShort))
                ->count();
        }

        $hidden = str_contains($contents, 'HiddenFromNavigation')
            || preg_match('/shouldRegisterNavigation[\s\S]*?return false;/', $contents) === 1;

        if ($usageCount >= 5 || in_array($modelBase, ['Tenant', 'FeatureFlag', 'PlatformSetting', 'PaymentGateway', 'PlatformTransaction', 'Refund', 'Settlement', 'Order', 'Receipt', 'AccessPass', 'Event', 'CallForProject', 'CrowdfundingCampaign', 'User', 'Role'], true)) {
            $recommendation = 'keep';
        } elseif ($hidden) {
            $recommendation = 'hidden/review';
        } elseif ($rowCount === '0' || $usageCount <= 2) {
            $recommendation = 'candidate';
        }

        return [
            'resource' => class_basename($resourceClass),
            'model' => $modelBase ?? '—',
            'table' => $table,
            'rows' => $rowCount,
            'usages' => (string) $usageCount,
            'nav' => $hidden ? 'hidden' : 'visible',
            'recommendation' => $recommendation,
            'path' => $relativePath,
        ];
    })->sortBy(['recommendation', 'resource'])->values()->all();

    $this->table(['Resource', 'Model', 'Table', 'Rows', 'Usages', 'Nav', 'Recommendation', 'Path'], $rows);

    return 0;
})->purpose('Audit Filament resources, model tables, row counts and coarse code usage');

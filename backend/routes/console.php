<?php

use App\Support\ReferenceData\CountryReferenceImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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

<?php

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

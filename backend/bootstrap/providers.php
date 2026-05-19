<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\PlatformPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    TenancyServiceProvider::class,
    PlatformPanelProvider::class,
    TenantPanelProvider::class,
];

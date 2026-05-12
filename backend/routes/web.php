<?php

use App\Http\Controllers\TenantPanelAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['signed', 'initialize.tenant.route'])->group(function (): void {
    Route::get('/tenants/{tenant}/admin/access/{user}', TenantPanelAccessController::class)
        ->whereNumber('user')
        ->name('tenant.panel.access');
});

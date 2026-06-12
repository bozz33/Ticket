<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * One-shot demo reset: wipes and recreates the full demo dataset across the active tenants
 * — content (10 items of every type, paid and free) then realistic buyer activity (orders,
 * receipts, passes, check-ins, refund requests, crowdfunding contributions, applications,
 * likes/follows, devices) plus central settlements and support tickets.
 *
 * Run with: php artisan db:seed --class=DemoFullSeeder
 */
class DemoFullSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TenantDemoEventsSeeder::class);
        $this->call(TenantDemoActivitySeeder::class);
    }
}

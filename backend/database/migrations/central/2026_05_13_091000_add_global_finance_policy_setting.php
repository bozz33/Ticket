<?php

use App\Models\PlatformSetting;
use App\Services\FinancePolicyService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(FinancePolicyService::class)->ensureSetting();
    }

    public function down(): void
    {
        PlatformSetting::query()
            ->where('group', FinancePolicyService::SETTING_GROUP)
            ->where('key', FinancePolicyService::SETTING_KEY)
            ->delete();
    }
};

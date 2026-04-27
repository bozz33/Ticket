<?php

namespace App\Support\Filament\Concerns;

use App\Services\SubscriptionGateService;
use App\Support\Tenancy\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait HasPanelPermission
{
    public static function shouldRegisterNavigation(): bool
    {
        if (! static::shouldShowInNavigation()) {
            return false;
        }

        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return static::allows('view');
    }

    public static function canViewAny(): bool
    {
        return static::allows('view');
    }

    public static function canCreate(): bool
    {
        return static::allows('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allows('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::allows('delete');
    }

    protected static function allows(string $action): bool
    {
        $user = Filament::auth()->user();

        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        $permissionPrefix = property_exists(static::class, 'permissionPrefix') ? static::$permissionPrefix : null;

        if ($permissionPrefix === null) {
            return false;
        }

        $requiredTenantFeature = property_exists(static::class, 'requiredTenantFeature') ? static::$requiredTenantFeature : null;

        if ($requiredTenantFeature !== null) {
            $tenant = app(TenantContext::class)->get();

            if ($tenant !== null && ! app(SubscriptionGateService::class)->allowsModule($tenant, $requiredTenantFeature)) {
                return false;
            }
        }

        $permission = sprintf('%s.%s', $permissionPrefix, $action);

        return method_exists($user, 'can') ? $user->can($permission) : false;
    }

    protected static function shouldShowInNavigation(): bool
    {
        if (! str_starts_with(static::class, 'App\\Filament\\Platform\\Resources\\')) {
            return true;
        }

        return in_array(static::class, [
            'App\\Filament\\Platform\\Resources\\CentralCategories\\CentralCategoryResource',
            'App\\Filament\\Platform\\Resources\\CommercialPolicies\\CommercialPolicyResource',
            'App\\Filament\\Platform\\Resources\\FeatureFlags\\FeatureFlagResource',
            'App\\Filament\\Platform\\Resources\\PaymentGateways\\PaymentGatewayResource',
            'App\\Filament\\Platform\\Resources\\Plans\\PlanResource',
            'App\\Filament\\Platform\\Resources\\PlatformAuditLogs\\PlatformAuditLogResource',
            'App\\Filament\\Platform\\Resources\\PlatformSettings\\PlatformSettingResource',
            'App\\Filament\\Platform\\Resources\\PlatformTransactions\\PlatformTransactionResource',
            'App\\Filament\\Platform\\Resources\\PlatformUsers\\PlatformUserResource',
            'App\\Filament\\Platform\\Resources\\Roles\\RoleResource',
            'App\\Filament\\Platform\\Resources\\SeoSettings\\SeoSettingResource',
            'App\\Filament\\Platform\\Resources\\Settlements\\SettlementResource',
            'App\\Filament\\Platform\\Resources\\TenantSubscriptions\\TenantSubscriptionResource',
            'App\\Filament\\Platform\\Resources\\Tenants\\TenantResource',
        ], true);
    }
}

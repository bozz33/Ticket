<?php

namespace App\Support\Filament\Concerns;

use App\Services\SubscriptionGateService;
use App\Support\Tenancy\TenantContext;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * @property static string|null $permissionPrefix
 * @property static string|null $requiredTenantFeature
 */
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

        $permissionPrefix = static::resolveStaticPropertyValue('permissionPrefix');

        if ($permissionPrefix === null) {
            return false;
        }

        $requiredTenantFeature = static::resolveStaticPropertyValue('requiredTenantFeature');

        if ($requiredTenantFeature !== null) {
            $tenant = app(TenantContext::class)->get();

            if ($tenant !== null && ! app(SubscriptionGateService::class)->allowsModule($tenant, $requiredTenantFeature)) {
                return false;
            }
        }

        $permission = sprintf('%s.%s', $permissionPrefix, $action);

        return method_exists($user, 'can') ? $user->can($permission) : false;
    }

    protected static function resolveStaticPropertyValue(string $property): mixed
    {
        $reflection = new \ReflectionClass(static::class);

        if (! $reflection->hasProperty($property)) {
            return null;
        }

        return $reflection->getStaticPropertyValue($property);
    }

    protected static function shouldShowInNavigation(): bool
    {
        if (! str_starts_with(static::class, 'App\\Filament\\Platform\\Resources\\')) {
            return true;
        }

        return in_array(static::class, [
            'App\\Filament\\Platform\\Resources\\CentralCategories\\CentralCategoryResource',
            'App\\Filament\\Platform\\Resources\\FeatureFlags\\FeatureFlagResource',
            'App\\Filament\\Platform\\Resources\\FrontMenus\\FrontMenuResource',
            'App\\Filament\\Platform\\Resources\\FrontPages\\FrontPageResource',
            'App\\Filament\\Platform\\Resources\\GatewayFeeRules\\GatewayFeeRuleResource',
            'App\\Filament\\Platform\\Resources\\PaymentGateways\\PaymentGatewayResource',
            'App\\Filament\\Platform\\Resources\\PayoutPolicies\\PayoutPolicyResource',
            'App\\Filament\\Platform\\Resources\\Plans\\PlanResource',
            'App\\Filament\\Platform\\Resources\\PlatformAuditLogs\\PlatformAuditLogResource',
            'App\\Filament\\Platform\\Resources\\PlatformSettings\\PlatformSettingResource',
            'App\\Filament\\Platform\\Resources\\PlatformFeeRules\\PlatformFeeRuleResource',
            'App\\Filament\\Platform\\Resources\\PlatformTransactions\\PlatformTransactionResource',
            'App\\Filament\\Platform\\Resources\\PlatformUsers\\PlatformUserResource',
            'App\\Filament\\Platform\\Resources\\Refunds\\RefundResource',
            'App\\Filament\\Platform\\Resources\\Roles\\RoleResource',
            'App\\Filament\\Platform\\Resources\\SeoSettings\\SeoSettingResource',
            'App\\Filament\\Platform\\Resources\\Settlements\\SettlementResource',
            'App\\Filament\\Platform\\Resources\\Tenants\\TenantResource',
        ], true);
    }
}

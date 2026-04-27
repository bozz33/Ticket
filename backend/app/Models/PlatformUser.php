<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class PlatformUser extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    protected $connection = 'central';

    protected string $guard_name = 'platform';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_super_admin',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'platform'
            && ($this->isSuperAdmin() || $this->can('platform.access'));
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin || $this->hasRole('super-admin');
    }

    public function getDefaultGuardName(): string
    {
        return $this->guard_name;
    }
}

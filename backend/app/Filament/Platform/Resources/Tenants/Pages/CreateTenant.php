<?php

namespace App\Filament\Platform\Resources\Tenants\Pages;

use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Services\Tenancy\ProvisionTenant;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected string|null $maxWidth = '7xl';

    protected array $tenantAdminCredentials = [];

    protected function handleRecordCreation(array $data): Model
    {
        $result = app(ProvisionTenant::class)->handle($data);
        $this->tenantAdminCredentials = $result['tenant_admin'];

        return $result['tenant'];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Tenant créé')
            ->body(implode(PHP_EOL, [
                'Email admin: ' . ($this->tenantAdminCredentials['email'] ?? '-'),
                'Mot de passe: ' . ($this->tenantAdminCredentials['password'] ?? '-'),
                'Login: ' . ($this->tenantAdminCredentials['login_url'] ?? '-'),
                'Lien public: ' . ($this->tenantAdminCredentials['public_url'] ?? '-'),
            ]));
    }
}

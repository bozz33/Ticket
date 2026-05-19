<?php

namespace App\Filament\Tenant\Resources\AccessPasses\Pages;

use App\Filament\Tenant\Resources\AccessPasses\AccessPassResource;
use App\Models\AccessPass;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Ticket\Ticketing\Contracts\AccessPassCatalog;
use Ticket\Ticketing\Contracts\AccessPassCheckin;

class VerifyAccessPasses extends Page
{
    protected static string $resource = AccessPassResource::class;

    protected string $view = 'filament.tenant.resources.access-passes.pages.verify-access-passes';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $navigationLabel = 'Scanner / vérifier';

    protected static ?string $title = 'Scanner / vérifier';

    public ?string $identifier = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $scanState = null;

    public function preview(AccessPassCatalog $accessPassCatalog, AccessPassCheckin $accessPassCheckin): void
    {
        $pass = $this->resolvePass($accessPassCatalog);

        if (! $pass) {
            return;
        }

        $result = $accessPassCheckin->preview($pass, request());

        $this->hydrateState($pass, $result, 'preview');
        $this->notifyForResult($result, 'Prévisualisation effectuée');
    }

    public function consume(AccessPassCatalog $accessPassCatalog, AccessPassCheckin $accessPassCheckin): void
    {
        $pass = $this->resolvePass($accessPassCatalog);

        if (! $pass) {
            return;
        }

        $result = $accessPassCheckin->consume($pass, request());

        $this->hydrateState($pass, $result, 'consume');
        $this->notifyForResult($result, 'Contrôle effectué');
    }

    public function resetPass(AccessPassCatalog $accessPassCatalog, AccessPassCheckin $accessPassCheckin): void
    {
        $pass = $this->resolvePass($accessPassCatalog);

        if (! $pass) {
            return;
        }

        $result = $accessPassCheckin->reset($pass, request());

        $this->hydrateState($pass, $result, 'reset');
        $this->notifyForResult($result, 'Pass réinitialisé');
    }

    public function clearState(): void
    {
        $this->identifier = null;
        $this->scanState = null;
    }

    /**
     * @return array<string, string>
     */
    public function supportedTypes(): array
    {
        return [
            'Billet événement' => 'Entrée ou participation à un événement.',
            'Inscription formation' => 'Présence confirmée pour une session ou une masterclass.',
            'Réservation stand' => 'Contrôle d’accès ou de présence pour un stand.',
            'Pass achat' => 'Pass générique lié à un achat ou une activation.',
        ];
    }

    private function resolvePass(AccessPassCatalog $accessPassCatalog): ?AccessPass
    {
        $this->validate([
            'identifier' => ['required', 'string', 'max:4000'],
        ]);

        $normalizedIdentifier = $this->normalizeIdentifier((string) $this->identifier);
        $this->identifier = $normalizedIdentifier;

        $pass = $accessPassCatalog->findByIdentifier($normalizedIdentifier);

        if (! $pass) {
            $this->scanState = null;

            Notification::make()
                ->danger()
                ->title('Pass introuvable')
                ->body('Aucun pass actif ne correspond à ce code ou à ce QR.')
                ->send();

            return null;
        }

        return $pass;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function hydrateState(AccessPass $pass, array $result, string $action): void
    {
        $freshPass = $pass->fresh(['order', 'offer', 'scans.scannedBy']);

        if (! $freshPass) {
            return;
        }

        $this->scanState = [
            'action' => $action,
            'result' => $result,
            'pass' => [
                'public_id' => $freshPass->public_id,
                'access_code' => $freshPass->access_code,
                'type' => $freshPass->type->value,
                'type_label' => $freshPass->type->label(),
                'status' => $freshPass->status->value,
                'status_label' => $result['message'] ?? $freshPass->status->value,
                'holder_name' => $freshPass->holder_name ?: 'Non renseigné',
                'holder_email' => $freshPass->holder_email ?: 'Non renseigné',
                'order_reference' => $freshPass->order?->reference ?: '—',
                'offer_title' => $freshPass->offer?->title ?: '—',
                'used_at' => $this->formatDate($freshPass->used_at),
                'expires_at' => $this->formatDate($freshPass->expires_at),
                'revoked_at' => $this->formatDate($freshPass->revoked_at),
                'revocation_reason' => $freshPass->revocation_reason ?: null,
                'scan_count' => $freshPass->scans->count(),
                'last_scan_at' => $this->formatDate($freshPass->scans->first()?->scanned_at),
                'last_scan_action' => $freshPass->scans->first()?->action ?: null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function notifyForResult(array $result, string $title): void
    {
        $notification = Notification::make()
            ->title($title)
            ->body((string) ($result['message'] ?? 'Opération terminée.'));

        if (($result['access_granted'] ?? false) === true) {
            $notification->success()->send();

            return;
        }

        $notification->warning()->send();
    }

    private function normalizeIdentifier(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return $trimmed;
        }

        if (str_starts_with($trimmed, '{')) {
            /** @var mixed $decoded */
            $decoded = json_decode($trimmed, true);

            if (is_array($decoded)) {
                if (filled($decoded['code'] ?? null)) {
                    return trim((string) $decoded['code']);
                }

                if (filled($decoded['public_id'] ?? null)) {
                    return trim((string) $decoded['public_id']);
                }
            }
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            $path = (string) parse_url($trimmed, PHP_URL_PATH);
            $segments = array_values(array_filter(explode('/', trim($path, '/'))));

            if ($segments !== []) {
                return trim((string) end($segments));
            }
        }

        return $trimmed;
    }

    private function formatDate(mixed $value): string
    {
        if (! $value instanceof CarbonInterface) {
            return '—';
        }

        return $value->format('d/m/Y H:i');
    }
}

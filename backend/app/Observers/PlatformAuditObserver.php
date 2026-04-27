<?php

namespace App\Observers;

use App\Models\PlatformAuditLog;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class PlatformAuditObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model, [
            'changes' => $model->getAttributes(),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();

        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $this->log('updated', $model, [
            'changes' => $changes,
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, [
            'changes' => $model->getOriginal(),
        ]);
    }

    protected function log(string $event, Model $model, array $context): void
    {
        if ($model instanceof PlatformAuditLog) {
            return;
        }

        app(AuditService::class)->log($event, $model, $context);
    }
}

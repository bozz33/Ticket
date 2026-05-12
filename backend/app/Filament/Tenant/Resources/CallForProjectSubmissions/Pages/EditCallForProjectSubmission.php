<?php

namespace App\Filament\Tenant\Resources\CallForProjectSubmissions\Pages;

use App\Filament\Support\Pages\EditRecordPage;
use App\Filament\Tenant\Resources\CallForProjectSubmissions\CallForProjectSubmissionResource;
use Filament\Support\Enums\Width;

class EditCallForProjectSubmission extends EditRecordPage
{
    protected static string $resource = CallForProjectSubmissionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? 'submitted') !== 'submitted' && blank($data['reviewed_at'] ?? null)) {
            $data['reviewed_at'] = now();
        }

        return $data;
    }
}

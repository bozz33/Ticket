<?php

namespace App\Filament\Tenant\Resources\FormSubmissions\Pages;

use App\Filament\Support\Pages\EditRecordPage;
use App\Filament\Tenant\Resources\FormSubmissions\FormSubmissionResource;
use Filament\Support\Enums\Width;

class EditFormSubmission extends EditRecordPage
{
    protected static string $resource = FormSubmissionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}

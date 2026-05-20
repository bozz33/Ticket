<?php

namespace App\Filament\Tenant\Resources\FormSubmissions\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\FormSubmissions\FormSubmissionResource;

class ListFormSubmissions extends ListRecordsPage
{
    protected static string $resource = FormSubmissionResource::class;
}

<?php

namespace App\Filament\Tenant\Resources\CallForProjectSubmissions\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\CallForProjectSubmissions\CallForProjectSubmissionResource;

class ListCallForProjectSubmissions extends ListRecordsPage
{
    protected static string $resource = CallForProjectSubmissionResource::class;
}

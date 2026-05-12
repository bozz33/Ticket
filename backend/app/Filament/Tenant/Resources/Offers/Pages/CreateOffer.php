<?php

namespace App\Filament\Tenant\Resources\Offers\Pages;

use App\Filament\Tenant\Resources\Offers\OfferResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateOffer extends CreateRecordPage
{
    protected static string $resource = OfferResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}

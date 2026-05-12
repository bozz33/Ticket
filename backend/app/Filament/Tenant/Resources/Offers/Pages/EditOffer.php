<?php

namespace App\Filament\Tenant\Resources\Offers\Pages;

use App\Filament\Tenant\Resources\Offers\OfferResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditOffer extends EditRecordPage
{
    protected static string $resource = OfferResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}

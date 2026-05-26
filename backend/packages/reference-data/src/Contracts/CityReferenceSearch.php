<?php

namespace Ticket\ReferenceData\Contracts;

use App\Models\City;

interface CityReferenceSearch
{
    /**
     * @return array<int, City>
     */
    public function search(?string $countryCode = null, ?string $query = null, int $limit = 50, bool $onlyActive = true): array;
}

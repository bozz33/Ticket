<?php

namespace Ticket\ReferenceData\Contracts;

interface CountryReferenceImport
{
    /**
     * @return array{countries:int,cities:int,active_countries_total:int,active_cities_total:int}
     */
    public function importFromFile(string $absolutePath): array;
}

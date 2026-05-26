<?php

namespace Ticket\Localization\Contracts;

interface PublicLocalizationCatalog
{
    public function publicLanguages(): array;

    public function publicTranslations(): array;
}

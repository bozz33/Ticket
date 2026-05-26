<?php

namespace Ticket\Seo\Contracts;

interface SeoMetadataCatalog
{
    public function publicSettings(): array;

    public function forRoute(?string $routePath): array;
}

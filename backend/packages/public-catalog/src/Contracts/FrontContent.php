<?php

namespace Ticket\PublicCatalog\Contracts;

use App\Models\FrontPage;

interface FrontContent
{
    public function publicMenus(): array;

    public function resolvePublishedPage(string $path): ?FrontPage;

    public function transformPage(FrontPage $page): array;

    public function publicPagePayload(string $path): ?array;

    public function publicPagesIndex(): array;

    public function defaultMenus(): array;
}

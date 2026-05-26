<?php

namespace Ticket\Cms\Contracts;

use App\Models\FrontPage;

interface FrontCmsContent
{
    public function publicMenus(): array;

    public function resolvePublishedPage(string $path): ?FrontPage;

    public function transformPage(FrontPage $page): array;

    public function publicPagePayload(string $path): ?array;

    public function publicPagesIndex(): array;

    public function defaultMenus(): array;
}

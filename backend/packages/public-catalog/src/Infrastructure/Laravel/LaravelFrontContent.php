<?php

namespace Ticket\PublicCatalog\Infrastructure\Laravel;

use App\Models\FrontPage;
use Ticket\Cms\Contracts\FrontCmsContent;
use Ticket\PublicCatalog\Contracts\FrontContent;

class LaravelFrontContent implements FrontContent
{
    public function __construct(private readonly FrontCmsContent $front) {}

    public function publicMenus(): array
    {
        return $this->front->publicMenus();
    }

    public function resolvePublishedPage(string $path): ?FrontPage
    {
        return $this->front->resolvePublishedPage($path);
    }

    public function transformPage(FrontPage $page): array
    {
        return $this->front->transformPage($page);
    }

    public function publicPagePayload(string $path): ?array
    {
        return $this->front->publicPagePayload($path);
    }

    public function publicPagesIndex(): array
    {
        return $this->front->publicPagesIndex();
    }

    public function defaultMenus(): array
    {
        return $this->front->defaultMenus();
    }
}

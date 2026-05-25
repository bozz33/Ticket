<?php

namespace Ticket\PublicCatalog\Infrastructure\Laravel;

use App\Models\CallForProject;
use Ticket\PublicCatalog\Application\CallForProjectApplicationFormService;
use Ticket\PublicCatalog\Contracts\CallForProjectFormBuilder;

class LaravelCallForProjectFormBuilder implements CallForProjectFormBuilder
{
    public function __construct(private readonly CallForProjectApplicationFormService $forms) {}

    public function schemaFor(CallForProject $callForProject): ?array
    {
        return $this->forms->schemaFor($callForProject);
    }
}

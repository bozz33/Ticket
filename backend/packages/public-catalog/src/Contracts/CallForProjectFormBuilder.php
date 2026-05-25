<?php

namespace Ticket\PublicCatalog\Contracts;

use App\Models\CallForProject;

interface CallForProjectFormBuilder
{
    public function schemaFor(CallForProject $callForProject): ?array;
}

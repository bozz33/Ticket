<?php

namespace Ticket\ContentTraining\Contracts;

use App\Models\Training;

interface TrainingContentCatalog
{
    public function list(?string $status = null): mixed;

    public function create(array $payload): Training;

    public function findByIdentifier(string $identifier): ?Training;
}

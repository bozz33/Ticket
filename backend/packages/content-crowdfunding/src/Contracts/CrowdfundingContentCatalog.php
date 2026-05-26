<?php

namespace Ticket\ContentCrowdfunding\Contracts;

use App\Models\CrowdfundingCampaign;

interface CrowdfundingContentCatalog
{
    public function list(?string $status = null): mixed;

    public function create(array $payload): CrowdfundingCampaign;

    public function findByIdentifier(string $identifier): ?CrowdfundingCampaign;
}

<?php

namespace Ticket\PublicCatalog\Infrastructure\Laravel;

use App\Models\CallForProject;
use App\Models\CallForProjectSubmission;
use Illuminate\Http\Request;
use Ticket\PublicCatalog\Application\CallForProjectSubmissionService;
use Ticket\PublicCatalog\Contracts\CallForProjectApplications;

class LaravelCallForProjectApplications implements CallForProjectApplications
{
    public function __construct(private readonly CallForProjectSubmissionService $submissions) {}

    public function submit(CallForProject $callForProject, Request $request): CallForProjectSubmission
    {
        return $this->submissions->submit($callForProject, $request);
    }
}

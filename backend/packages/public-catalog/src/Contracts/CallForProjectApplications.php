<?php

namespace Ticket\PublicCatalog\Contracts;

use App\Models\CallForProject;
use App\Models\CallForProjectSubmission;
use Illuminate\Http\Request;

interface CallForProjectApplications
{
    public function submit(CallForProject $callForProject, Request $request): CallForProjectSubmission;
}

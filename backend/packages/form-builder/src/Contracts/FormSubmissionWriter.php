<?php

namespace Ticket\FormBuilder\Contracts;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;

interface FormSubmissionWriter
{
    public function submit(FormDefinition $formDefinition, Request $request): FormSubmission;
}

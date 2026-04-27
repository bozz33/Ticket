<?php

namespace App\Http\Requests\Api\V1\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTenantPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists('central.plans', 'id')],
            'status' => ['nullable', 'string', 'max:50'],
            'started_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ];
    }
}

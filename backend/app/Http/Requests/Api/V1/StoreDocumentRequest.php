<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_profile_id' => ['nullable', 'integer', Rule::exists('tenant.organization_profiles', 'id')],
            'resource_type_code' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('tenant.documents', 'slug')],
            'description' => ['nullable', 'string'],
            'disk' => ['nullable', 'string', 'max:50'],
            'path' => ['required', 'string', 'max:500'],
            'mime_type' => ['nullable', 'string', 'max:150'],
            'extension' => ['nullable', 'string', 'max:20'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
            'visibility' => ['nullable', Rule::in(['private', 'public'])],
            'is_active' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ];
    }
}

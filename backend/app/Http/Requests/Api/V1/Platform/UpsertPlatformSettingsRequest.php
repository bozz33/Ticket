<?php

namespace App\Http\Requests\Api\V1\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.group' => ['nullable', 'string', 'max:100'],
            'items.*.key' => ['required', 'string', 'max:150'],
            'items.*.value' => ['nullable', 'array'],
            'items.*.type' => ['nullable', 'string', 'max:50'],
            'items.*.is_public' => ['sometimes', 'boolean'],
        ];
    }
}

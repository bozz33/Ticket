<?php

namespace App\Http\Requests\Api\V1\Platform;

use App\Enums\CategoryScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCentralTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('central.tags', 'slug')],
            'description' => ['nullable', 'string'],
            'module_scope' => ['nullable', Rule::in(CategoryScope::values())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'meta' => ['nullable', 'array'],
        ];
    }
}

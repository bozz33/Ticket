<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_profile_id' => ['nullable', 'integer', Rule::exists('tenant.organization_profiles', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('tenant.categories', 'id')],
            'public_status_code' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('tenant.events', 'slug')],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'city_id' => ['nullable', 'integer'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'cover_image_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
            'dates' => ['nullable', 'array'],
            'dates.*.starts_at' => ['required_with:dates', 'date'],
            'dates.*.ends_at' => ['nullable', 'date'],
            'dates.*.timezone' => ['nullable', 'string', 'max:100'],
            'dates.*.is_all_day' => ['sometimes', 'boolean'],
            'dates.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'dates.*.meta' => ['nullable', 'array'],
        ];
    }
}

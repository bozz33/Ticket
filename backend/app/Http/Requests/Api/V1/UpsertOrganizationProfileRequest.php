<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpsertOrganizationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'banner_url' => ['nullable', 'url', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'meta' => ['nullable', 'array'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.type' => ['nullable', 'string', 'max:50'],
            'contacts.*.label' => ['nullable', 'string', 'max:255'],
            'contacts.*.value' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],
            'contacts.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'contacts.*.meta' => ['nullable', 'array'],
            'social_links' => ['nullable', 'array'],
            'social_links.*.platform' => ['required_with:social_links', 'string', 'max:50'],
            'social_links.*.label' => ['nullable', 'string', 'max:255'],
            'social_links.*.url' => ['required_with:social_links', 'url', 'max:255'],
            'social_links.*.is_public' => ['sometimes', 'boolean'],
            'social_links.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'social_links.*.meta' => ['nullable', 'array'],
        ];
    }
}

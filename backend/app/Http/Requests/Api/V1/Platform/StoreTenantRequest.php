<?php

namespace App\Http\Requests\Api\V1\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('central.tenants', 'slug')],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'locale' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'timezone'],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('central.tenant_domains', 'domain')],
            'database_name' => ['nullable', 'string', 'max:255', Rule::unique('central.tenants', 'database_name')],
            'database_host' => ['nullable', 'string', 'max:255'],
            'database_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'database_username' => ['nullable', 'string', 'max:255'],
            'database_password' => ['nullable', 'string', 'max:255'],
            'database_options' => ['nullable', 'array'],
            'plan_id' => ['nullable', 'integer', Rule::exists('central.plans', 'id')],
            'admin' => ['required', 'array'],
            'admin.name' => ['nullable', 'string', 'max:255'],
            'admin.username' => ['nullable', 'string', 'min:3', 'max:120', 'alpha_dash'],
            'admin.email' => ['required', 'email:rfc,dns', 'max:255'],
            'admin.password' => ['required', 'string', 'min:8', 'max:255'],
            'admin.phone' => ['nullable', 'string', 'max:50'],
            'admin.locale' => ['nullable', 'string', 'max:10'],
            'admin.timezone' => ['nullable', 'timezone'],
            'meta' => ['nullable', 'array'],
            'activate' => ['sometimes', 'boolean'],
        ];
    }
}

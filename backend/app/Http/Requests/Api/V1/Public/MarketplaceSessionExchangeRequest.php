<?php

namespace App\Http\Requests\Api\V1\Public;

use Illuminate\Foundation\Http\FormRequest;

class MarketplaceSessionExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_tenant' => ['required', 'string', 'max:255'],
            'target_tenant' => ['required', 'string', 'max:255'],
            'token_name' => ['nullable', 'string', 'max:80'],
        ];
    }
}

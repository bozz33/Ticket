<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_reference' => ['required', 'string', 'max:255'],
            'reason_code' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

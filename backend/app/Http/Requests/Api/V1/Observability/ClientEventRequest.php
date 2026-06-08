<?php

namespace App\Http\Requests\Api\V1\Observability;

use Illuminate\Foundation\Http\FormRequest;

class ClientEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:60'],
            'name' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:1000'],
            'path' => ['nullable', 'string', 'max:500'],
            'rating' => ['nullable', 'string', 'max:40'],
            'value' => ['nullable', 'numeric'],
            'stack' => ['nullable', 'string', 'max:8000'],
        ];
    }
}

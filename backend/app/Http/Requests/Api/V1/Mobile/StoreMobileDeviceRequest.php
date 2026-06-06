<?php

namespace App\Http\Requests\Api\V1\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMobileDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:191'],
            'platform' => ['required', 'string', Rule::in(['ios', 'android', 'web'])],
            'push_provider' => ['nullable', 'string', Rule::in(['expo', 'fcm', 'apns'])],
            'push_token' => ['required', 'string', 'max:4096'],
            'app_version' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:191'],
            'locale' => ['nullable', 'string', 'max:32'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'meta' => ['nullable', 'array'],
        ];
    }
}

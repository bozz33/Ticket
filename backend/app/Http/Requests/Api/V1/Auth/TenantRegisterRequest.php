<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\VerifiedMailboxCandidate;
use Illuminate\Foundation\Http\FormRequest;

class TenantRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email', new VerifiedMailboxCandidate],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'token_name' => ['sometimes', 'string', 'max:100'],
        ];
    }
}

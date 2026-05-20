<?php

namespace App\Http\Requests\Api\V1\Public;

use Illuminate\Foundation\Http\FormRequest;

class PublicPaymentInitializeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offer' => ['required_without:ticket', 'nullable', 'string'],
            'ticket' => ['required_without:offer', 'nullable', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email:rfc', 'max:255'],
            'buyer_phone' => ['nullable', 'string', 'max:50'],
            'ticket_reservation' => ['nullable', 'string', 'max:80'],
            'content_module' => ['nullable', 'string', 'max:100'],
            'content_slug' => ['nullable', 'string', 'max:255'],
            'contributor_display_name' => ['nullable', 'string', 'max:255'],
            'contributor_is_anonymous' => ['nullable', 'boolean'],
            'callback_url' => ['required', 'url', 'max:2048'],
        ];
    }
}

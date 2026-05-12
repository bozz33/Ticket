<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class VerifiedMailboxCandidate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = Str::lower(trim((string) $value));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail('Adresse e-mail invalide.');

            return;
        }

        if (! (bool) config('ticket.email_validation.dns_check', app()->isProduction())) {
            return;
        }

        $domain = Str::afterLast($email, '@');

        if ($domain === '' || (! checkdnsrr($domain, 'MX') && ! checkdnsrr($domain, 'A') && ! checkdnsrr($domain, 'AAAA'))) {
            $fail('Le domaine de cette adresse e-mail ne semble pas recevoir d’e-mails.');
        }
    }
}

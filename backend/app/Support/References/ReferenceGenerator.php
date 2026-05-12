<?php

namespace App\Support\References;

use Illuminate\Support\Str;

class ReferenceGenerator
{
    public function generate(string $prefix, ?string $currencyCode = null): string
    {
        return implode('-', [
            strtoupper(trim($prefix)),
            now()->format('ymd'),
            now()->format('Hi'),
            strtoupper(Str::random(6)),
        ]);
    }
}

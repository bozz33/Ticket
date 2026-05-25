<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class PlatformMailSettings
{
    public const SETTING_KEY = 'mail.smtp';

    public function apply(): void
    {
        try {
            if (! Schema::connection('central')->hasTable('platform_settings')) {
                return;
            }

            $setting = PlatformSetting::query()->where('key', self::SETTING_KEY)->first();
        } catch (QueryException) {
            return;
        }

        $value = (array) ($setting?->value ?? []);

        if (empty($value['host'])) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $value['host']);
        Config::set('mail.mailers.smtp.port', (int) ($value['port'] ?? 587));
        Config::set('mail.mailers.smtp.encryption', $value['encryption'] ?? 'tls');
        Config::set('mail.mailers.smtp.username', $value['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $value['password'] ?? null);
        Config::set('mail.from.address', $value['from_address'] ?? config('mail.from.address'));
        Config::set('mail.from.name', $value['from_name'] ?? config('mail.from.name'));
    }

    public function sendTestMessage(?string $recipient = null): void
    {
        $this->apply();

        $to = $recipient ?: (string) config('mail.from.address');

        Mail::raw('Ceci est un e-mail de test SMTP envoyé depuis le panel super-admin Ticket.', function ($message) use ($to): void {
            $message->to($to)
                ->subject('Test SMTP Ticket');
        });
    }
}

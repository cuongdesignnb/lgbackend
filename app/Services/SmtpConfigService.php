<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class SmtpConfigService
{
    /**
     * Override Laravel mail config with database SMTP settings.
     * Call this before sending any mail.
     */
    public static function apply(): void
    {
        $host = Setting::get('smtp_host');
        $port = Setting::get('smtp_port');
        $username = Setting::get('smtp_username');
        $password = Setting::get('smtp_password');
        $encryption = Setting::get('smtp_encryption', 'tls');
        $fromAddress = Setting::get('smtp_from_address');
        $fromName = Setting::get('smtp_from_name');

        // Only override if at least host is configured
        if (!$host) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', (int) ($port ?: 587));
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        $realEncryption = ($encryption && $encryption !== 'none') ? $encryption : null;
        Config::set('mail.mailers.smtp.encryption', $realEncryption);

        // Also set the scheme for Laravel 11+
        if ($encryption === 'ssl') {
            Config::set('mail.mailers.smtp.scheme', 'smtps');
        } else {
            Config::set('mail.mailers.smtp.scheme', null);
        }

        if ($fromAddress) {
            Config::set('mail.from.address', $fromAddress);
        }
        if ($fromName) {
            Config::set('mail.from.name', $fromName);
        }

        // Purge the cached mailer so it re-reads config
        app('mail.manager')->purge('smtp');
    }
}

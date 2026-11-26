<?php

namespace App\Support;

use App\Models\Integration;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleLogin
{
    public static function callbackUrl(): string
    {
        return rtrim(config('app.url'), '/').'/auth/google/callback';
    }

    public static function settings(): ?array
    {
        $record = Integration::where('integration_key', 'google_login')->first();
        if (!$record?->is_active) return null;
        try { $settings = $record->configuration ?? []; }
        catch (\Illuminate\Contracts\Encryption\DecryptException) { return null; }
        if (!is_string($settings['client_id'] ?? null) || !is_string($settings['client_secret'] ?? null)
            || trim($settings['client_id']) === '' || trim($settings['client_secret']) === '') return null;
        return $settings;
    }

    public static function enabled(): bool { return static::settings() !== null; }

    public static function fingerprint(array $settings): string
    {
        return hash('sha256', json_encode([$settings['client_id'], $settings['client_secret'], static::callbackUrl()]));
    }

    public function provider(Request $request, array $settings): GoogleProvider
    {
        return (new GoogleProvider($request, $settings['client_id'], $settings['client_secret'], static::callbackUrl(),
            ['timeout' => 15, 'connect_timeout' => 5, 'allow_redirects' => false]))
            ->enablePKCE()->with(['prompt' => 'select_account']);
    }
}

<?php

namespace App\Services;

use App\Models\{Integration, NotificationDelivery};
use Illuminate\Support\Facades\Mail;

class CampaignMailer
{
    public function settings(): ?array
    {
        $record = Integration::where('integration_key', 'smtp')->where('is_active', true)->first();
        $settings = $record?->configuration;
        if (!$settings) return null;
        foreach (['host', 'port', 'username', 'password', 'encryption', 'from_address'] as $key) {
            if (trim($settings[$key] ?? '') === '') return null;
        }
        $settings['from_name'] = \App\Support\SiteBrand::name();
        return $settings;
    }

    public function send(NotificationDelivery $delivery): void
    {
        $this->sendMessage($delivery->recipient_email, $delivery->subject, $delivery->content);
    }

    public function sendMessage(string $recipient, string $subject, string $content): void
    {
        $settings = $this->settings();
        if (!$settings) throw new \RuntimeException('SMTP bağlantısı etkin değil.');
        // A fresh transport avoids stale credentials in a long-running process.
        $mailer = Mail::build([
            'transport' => 'smtp', 'scheme' => $settings['encryption'] === 'ssl' ? 'smtps' : 'smtp',
            'host' => $settings['host'], 'port' => (int) $settings['port'],
            'username' => $settings['username'], 'password' => $settings['password'],
            'require_tls' => true, 'timeout' => 20,
        ]);
        $mailer->raw($content, function ($message) use ($recipient, $subject, $settings) {
            $message->from($settings['from_address'], $settings['from_name'])
                ->to($recipient)->subject($subject);
        });
    }
}

<?php

namespace App\Services;

use App\Models\{AutomationRule, ExamAttempt, NotificationCampaign, NotificationDelivery, User};
use App\Support\NotificationAudience;
use Illuminate\Support\Facades\{Cache, DB};

class NotificationProcessor
{
    public function run(int $limit = 100): void
    {
        // Serializes the scanner and sender across scheduled/manual invocations.
        $lock = Cache::lock('notification-processor', 3600);
        if (!$lock->get()) return;
        try {
            $this->prepareCampaigns();
            $this->prepareAutomations();
            // An interrupted external request may already have delivered the mail.
            // Never resend such requests automatically.
            NotificationDelivery::where('status', 'processing')->where('attempted_at', '<=', now()->subHour())
                ->update(['status' => 'uncertain', 'error' => 'Gönderim sırasında işlem kesildi. Yeniden gönderilmeden önce sağlayıcı kaydı kontrol edilmeli.']);
            NotificationDelivery::where('status', 'pending')->where('available_at', '<=', now())->orderBy('id')
                ->limit(max(1, min($limit, 100)))->get()->each(fn ($delivery) => $this->deliver($delivery));
            NotificationCampaign::where('execution_enabled', true)->whereNotNull('prepared_at')
                ->whereIn('status', ['Gönderiliyor', 'Hata'])->each(fn ($campaign) => $this->finishCampaign($campaign));
            Cache::put('notifications.last_run_at', now()->toIso8601String(), now()->addDays(7));
        } finally {
            $lock->release();
        }
    }

    private function prepareCampaigns(): void
    {
        NotificationCampaign::where('execution_enabled', true)->whereNull('prepared_at')
            ->whereIn('status', ['Sırada', 'Planlandı'])->where('scheduled_at', '<=', now())
            ->orderBy('id')->chunkById(50, function ($campaigns) {
                foreach ($campaigns as $campaign) DB::transaction(function () use ($campaign) {
                    $campaign = NotificationCampaign::lockForUpdate()->find($campaign->id);
                    if (!$campaign || !$campaign->execution_enabled || $campaign->prepared_at || !in_array($campaign->status, ['Sırada', 'Planlandı'])) return;
                    if (!$campaign->target_groups || !$campaign->content || $campaign->is_push || (!$campaign->is_email && !$campaign->is_in_app)) {
                        $campaign->update(['status' => 'Hata']);
                        return;
                    }
                    $channels = array_keys(array_filter(['in_app' => $campaign->is_in_app, 'email' => $campaign->is_email]));
                    $count = 0;
                    NotificationAudience::query($campaign->target_groups)->chunkById(200, function ($users) use ($campaign, $channels, &$count) {
                        foreach ($users as $user) {
                            $this->enqueue($user, $channels, $campaign->title, $campaign->content, 'campaign:'.$campaign->id, $campaign->id);
                            $count++;
                        }
                    });
                    $campaign->update(['recipient_count' => $count, 'prepared_at' => now(), 'status' => 'Gönderiliyor']);
                });
            });
    }

    private function prepareAutomations(): void
    {
        AutomationRule::where('is_active', true)->whereNotNull('activated_at')->whereNotNull('trigger')
            ->chunkById(50, function ($rules) {
                foreach ($rules as $rule) {
                    if (!$rule->subject || !$rule->content || !$rule->delivery_channels || !$rule->target_groups) continue;
                    $cutoff = now()->subHours($rule->wait_hours);
                    $users = NotificationAudience::query($rule->target_groups)->where('is_admin', false);
                    if ($rule->trigger === 'registered') {
                        $users->whereBetween('created_at', [$rule->activated_at, $cutoff])->chunkById(200, function ($rows) use ($rule) {
                            foreach ($rows as $user) $this->enqueueRule($rule, $user, 'registered:'.$user->id);
                        });
                    } elseif ($rule->trigger === 'exam_passed') {
                        ExamAttempt::whereIn('user_id', $users->select('id'))->where('is_passed', true)
                            ->whereBetween('completed_at', [$rule->activated_at, $cutoff])->with('user')->chunkById(200, function ($rows) use ($rule) {
                                foreach ($rows as $attempt) if ($attempt->user) $this->enqueueRule($rule, $attempt->user, 'exam:'.$attempt->id);
                            });
                    } elseif ($rule->trigger === 'inactive') {
                        // Only activity since activation participates; never mail historical users on enabling.
                        $users->whereRaw('COALESCE(last_active_at, created_at) >= ?', [$rule->activated_at])
                            ->whereRaw('COALESCE(last_active_at, created_at) <= ?', [$cutoff])
                            ->chunkById(200, function ($rows) use ($rule) {
                                foreach ($rows as $user) $this->enqueueRule($rule, $user, 'inactive:'.$user->id.':'.($user->last_active_at ?? $user->created_at)->timestamp);
                            });
                    }
                    $rule->updateQuietly(['last_checked_at' => now()]);
                }
            });
    }

    private function enqueueRule(AutomationRule $rule, User $user, string $event): void
    {
        DB::transaction(function () use ($rule, $user, $event) {
            $rule = AutomationRule::lockForUpdate()->find($rule->id);
            if (!$rule || !$rule->is_active || !$rule->activated_at) return;
            if ($this->enqueue($user, $rule->delivery_channels, $rule->subject, $rule->content, 'rule:'.$rule->id.':'.$event, null, $rule->id)) {
                $rule->updateQuietly(['last_triggered_at' => now()]);
            }
        });
    }

    private function enqueue(User $user, array $channels, string $subject, string $content, string $event, ?int $campaignId = null, ?int $ruleId = null): bool
    {
        $created = false;
        foreach (array_intersect($channels, array_keys(NotificationDelivery::CHANNELS)) as $channel) {
            $delivery = NotificationDelivery::firstOrCreate(['dedupe_key' => hash('sha256', $event.':'.$user->id.':'.$channel)], [
                'campaign_id' => $campaignId, 'rule_id' => $ruleId, 'user_id' => $user->id, 'event_key' => $event,
                'channel' => $channel, 'subject' => str_replace('{ad}', $user->name, $subject),
                'content' => str_replace('{ad}', $user->name, $content), 'recipient_email' => $channel === 'email' ? $user->email : null,
                'available_at' => now(), 'status' => 'pending',
            ]);
            $created = $created || $delivery->wasRecentlyCreated;
        }
        return $created;
    }

    private function deliver(NotificationDelivery $delivery): void
    {
        $claimed = DB::transaction(function () use ($delivery) {
            // The same parent locks are used by panel edits and cancellation.
            $campaign = $delivery->campaign_id ? NotificationCampaign::lockForUpdate()->find($delivery->campaign_id) : null;
            $rule = $delivery->rule_id ? AutomationRule::lockForUpdate()->find($delivery->rule_id) : null;
            if (($delivery->campaign_id && (!$campaign || !$campaign->execution_enabled || $campaign->status !== 'Gönderiliyor'))
                || ($delivery->rule_id && (!$rule || !$rule->is_active || !$rule->activated_at))) {
                NotificationDelivery::whereKey($delivery->id)->where('status', 'pending')->update(['status' => 'cancelled']);
                return false;
            }
            if ($rule?->trigger === 'inactive') {
                $user = User::find($delivery->user_id);
                $event = $user ? 'rule:'.$rule->id.':inactive:'.$user->id.':'.($user->last_active_at ?? $user->created_at)->timestamp : null;
                if ($event !== $delivery->event_key) {
                    NotificationDelivery::whereKey($delivery->id)->where('status', 'pending')->update(['status' => 'cancelled']);
                    return false;
                }
            }
            return NotificationDelivery::whereKey($delivery->id)->where('status', 'pending')
                ->update(['status' => 'processing', 'attempted_at' => now()]) === 1;
        });
        if (!$claimed) return;
        try {
            if ($delivery->channel === 'email') {
                $mailer = app(CampaignMailer::class);
                if (!$mailer->settings()) {
                    $delivery->update(['status' => 'failed', 'error' => 'SMTP bağlantısı etkin değil. Entegrasyonlar bölümünden yapılandırın.']);
                    return;
                }
                $mailer->send($delivery);
            } elseif ($delivery->channel !== 'in_app') {
                throw new \RuntimeException('Desteklenmeyen kanal.');
            }
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
        } catch (\Throwable $exception) {
            // SMTP exceptions can contain credentials or recipient data; do not expose the raw message.
            $delivery->update(['status' => 'uncertain', 'error' => 'Gönderim doğrulanamadı. SMTP sağlayıcısındaki kaydı kontrol edin; otomatik tekrar gönderilmedi.']);
        }
    }

    private function finishCampaign(NotificationCampaign $campaign): void
    {
        if ($campaign->deliveries()->whereIn('status', ['pending', 'processing'])->exists()) return;
        $failed = $campaign->deliveries()->whereIn('status', ['failed', 'uncertain'])->exists();
        $campaign->updateQuietly(['status' => $failed ? 'Hata' : ($campaign->recipient_count ? 'Gönderildi' : 'Alıcı Yok'),
            'sent_at' => $failed || !$campaign->recipient_count ? null : $campaign->deliveries()->max('sent_at')]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['available_at' => 'datetime', 'attempted_at' => 'datetime', 'sent_at' => 'datetime', 'read_at' => 'datetime'];

    public const STATUS_LABELS = [
        'pending' => 'Bekliyor', 'processing' => 'Gönderiliyor', 'sent' => 'Gönderildi',
        'failed' => 'Başarısız', 'uncertain' => 'Sonuç doğrulanamadı', 'cancelled' => 'İptal edildi',
    ];
    public const CHANNELS = ['in_app' => 'Site İçi Bildirim', 'email' => 'E-posta'];

    public function user() { return $this->belongsTo(User::class); }
    public function campaign() { return $this->belongsTo(NotificationCampaign::class, 'campaign_id'); }
    public function rule() { return $this->belongsTo(AutomationRule::class, 'rule_id'); }
}

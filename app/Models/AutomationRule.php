<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    public const TRIGGERS = ['registered' => 'Yeni üyelikten sonra', 'exam_passed' => 'Başarılı sınav / testten sonra', 'inactive' => 'Siteye gelmeyen üyeye hatırlatma'];
    protected $fillable = ['name', 'is_active', 'channels', 'wait_hours', 'last_checked_at', 'last_triggered_at', 'trigger', 'target_groups', 'delivery_channels', 'subject', 'content', 'activated_at'];
    protected $casts = ['is_active' => 'boolean', 'wait_hours' => 'integer', 'last_checked_at' => 'datetime', 'last_triggered_at' => 'datetime', 'activated_at' => 'datetime', 'target_groups' => 'array', 'delivery_channels' => 'array'];

    public function getChannelsAttribute($value): string
    {
        return $this->delivery_channels ? implode(', ', array_map(
            fn ($channel) => NotificationDelivery::CHANNELS[$channel] ?? $channel, $this->delivery_channels
        )) : (string) $value;
    }

    protected static function booted(): void
    {
        static::saving(function (self $rule) {
            if ($rule->delivery_channels) $rule->attributes['channels'] = null;
        });
    }

    public function deliveries() { return $this->hasMany(NotificationDelivery::class, 'rule_id'); }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationCampaign extends Model
{
    protected $fillable = ['title', 'type', 'target', 'target_groups', 'is_email', 'is_push', 'is_in_app', 'execution_enabled', 'prepared_at', 'status', 'scheduled_at', 'sent_at', 'recipient_count', 'content'];
    protected $casts = ['target_groups' => 'array', 'is_email' => 'boolean', 'is_push' => 'boolean', 'is_in_app' => 'boolean', 'execution_enabled' => 'boolean', 'prepared_at' => 'datetime', 'scheduled_at' => 'datetime', 'sent_at' => 'datetime', 'recipient_count' => 'integer'];

    public function getTargetAttribute($value): string
    {
        return $this->target_groups ? \App\Support\NotificationAudience::label($this->target_groups) : (string) $value;
    }

    protected static function booted(): void
    {
        static::saving(function (self $campaign) {
            if ($campaign->target_groups) $campaign->attributes['target'] = null;
        });
    }

    public function deliveries() { return $this->hasMany(NotificationDelivery::class, 'campaign_id'); }

}

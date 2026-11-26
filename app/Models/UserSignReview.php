<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSignReview extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'traffic_sign_id',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class, 'traffic_sign_id');
    }
}

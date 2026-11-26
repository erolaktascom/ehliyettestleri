<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoNotFoundLog extends Model
{
    protected $fillable = ['url', 'request_count', 'first_seen_at', 'last_seen_at'];
    protected $casts = ['request_count' => 'integer', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'user_name', 'record_type', 'record_title', 'changed_fields'];
    protected $casts = [];

    public function user() { return $this->belongsTo(User::class); }
}

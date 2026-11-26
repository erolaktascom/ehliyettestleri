<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = ['name', 'provider', 'is_active', 'integration_key', 'configuration'];
    protected $casts = ['is_active' => 'boolean', 'configuration' => 'encrypted:array'];
    protected $hidden = ['configuration'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumPlan extends Model
{
    protected $fillable = ['name', 'short_code', 'duration_days', 'price_cents', 'order', 'is_published'];
    protected $casts = ['duration_days' => 'integer', 'price_cents' => 'integer', 'order' => 'integer', 'is_published' => 'boolean'];

}

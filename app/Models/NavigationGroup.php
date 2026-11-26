<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationGroup extends Model
{
    protected $fillable = ['name', 'order', 'is_active'];
    protected $casts = ['order' => 'integer', 'is_active' => 'boolean'];

    public function scopeForAdminMenu($query, string $menu) {
        return match ($menu) {
            'footer' => $query->where('key', '!=', 'apps'),
            'apps' => $query->where('key', 'apps'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function items() {
        return $this->hasMany(NavigationItem::class, 'footer_group', 'key')->where('menu', 'footer');
    }

    public static function footerGroups()
    {
        return static::orderBy('order')->orderBy('id')->get();
    }
}

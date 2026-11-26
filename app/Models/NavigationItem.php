<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    public const MENUS = ['desktop' => 'Masaüstü Menüsü', 'mobile' => 'Mobil Menüsü', 'footer' => 'Alt Bilgi Menüsü'];
    public const ADMIN_MENUS = self::MENUS + ['apps' => 'Uygulamalar', 'footer-bottom' => 'Alt Bağlantılar'];

    public function scopeForAdminMenu(\Illuminate\Database\Eloquent\Builder $query, string $menu): \Illuminate\Database\Eloquent\Builder
    {
        return match ($menu) {
            'footer' => $query->where('menu', 'footer')->whereNotNull('footer_group')->where('footer_group', '!=', 'apps'),
            'apps' => $query->where('menu', 'footer')->where('footer_group', 'apps'),
            'footer-bottom' => $query->where('menu', 'footer')->whereNull('footer_group'),
            default => $query->where('menu', $menu),
        };
    }

    public const AUDIENCES = ['Herkes', 'Üyeler', 'Misafirler'];
    protected $fillable = ['name', 'url', 'audience', 'menu', 'footer_group', 'is_active', 'order'];
    protected $casts = ['is_active' => 'boolean', 'order' => 'integer'];

    public static function safeUrl(string $url): bool
    {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url) || preg_match('/[\x00-\x1f\x7f\\\\]/', rawurldecode($url))) return false;
        if (str_starts_with($url, '/') && !str_starts_with(rawurldecode($url), '//')) return true;
        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https']);
    }

    public static function visibleMenus(): \Illuminate\Support\Collection
    {
        $audiences = auth()->check() ? ['Herkes', 'everyone', 'Üyeler', 'members'] : ['Herkes', 'everyone', 'Misafirler', 'guests'];
        return static::where('is_active', true)->whereIn('audience', $audiences)->orderBy('order')->orderBy('id')->get()
            ->filter(fn ($item) => static::safeUrl($item->url)
                || ($item->menu === 'footer' && $item->footer_group === 'apps' && $item->url === ''))->groupBy('menu');
    }
}

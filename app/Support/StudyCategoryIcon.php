<?php

namespace App\Support;

use Illuminate\Support\Str;

class StudyCategoryIcon
{
    public const OPTIONS = [
        'book' => 'Kitap',
        'exam' => 'Sınav kâğıdı',
        'first-aid' => 'İlk yardım',
        'gear' => 'Dişli',
        'courtesy' => 'Trafik adabı',
        'sign' => 'Uyarı işareti',
        'parking' => 'Park işareti',
        'restriction' => 'Yasak işareti',
        'info' => 'Bilgi işareti',
        'play' => 'Oynat',
    ];

    public static function selected(?string $icon): ?string
    {
        return isset(self::OPTIONS[$icon]) ? $icon : null;
    }

    public static function resolve(?string $icon, string $name, string $fallback = 'book'): string
    {
        return self::selected($icon) ?? self::for($name, $fallback);
    }

    public static function for(string $name, string $fallback = 'book'): string
    {
        $name = Str::slug($name);
        return match (true) {
            str_contains($name, 'ilk-yardim') => 'first-aid',
            str_contains($name, 'arac'), str_contains($name, 'motor') => 'gear',
            str_contains($name, 'adab') => 'courtesy',
            str_contains($name, 'animasyon') => 'play',
            str_contains($name, 'park'), str_contains($name, 'duraklama') => 'parking',
            str_contains($name, 'tanzim') => 'restriction',
            str_contains($name, 'bilgi-isaret') => 'info',
            str_contains($name, 'trafik'), str_contains($name, 'tehlike') => 'sign',
            default => $fallback,
        };
    }
}

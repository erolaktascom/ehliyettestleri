<?php
namespace App\Support;

use App\Models\BlogPost;
use Illuminate\Support\Str;

class SeoPageCatalog
{
    public const PAGES = [
        'home' => 'Ana Sayfa', 'exams.index' => 'Deneme Sınavları',
        'topic-tests.categories' => 'Konu Testleri', 'lectures.index' => 'Konu Anlatımları',
        'blog.index' => 'Blog', 'contact.show' => 'İletişim',
        'traffic-signs.index' => 'Trafik İşaretleri',
        'animated-questions.index' => 'Animasyonlu Sorular',
    ];

    public static function choices(): array
    {
        return self::PAGES;
    }

    public static function url(string $key): string
    {
        return route($key);
    }
}
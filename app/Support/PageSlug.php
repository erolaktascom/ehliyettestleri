<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class PageSlug
{
    public static function validate(string $slug): void
    {
        if (!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug)) {
            throw ValidationException::withMessages(['slug' => 'Adres yalnız küçük harf, rakam ve aralarda tire içermelidir.']);
        }
        $reserved = ['sayfa', 'storage', 'build', 'vendor'];
        foreach (Route::getRoutes() as $route) {
            if (in_array($route->getName(), ['page.show', 'about'], true)) continue;
            $first = explode('/', trim($route->uri(), '/'))[0];
            if ($first !== '' && !str_contains($first, '{')) $reserved[] = $first;
        }
        if (in_array($slug, $reserved, true) || SeoRedirectRules::protectedPath('/'.$slug) || file_exists(public_path($slug))) {
            throw ValidationException::withMessages(['slug' => 'Bu adres sistem tarafından kullanılıyor. Farklı bir sayfa adresi seçin.']);
        }
    }
}
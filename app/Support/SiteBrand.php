<?php
namespace App\Support;

use App\Models\SiteSetting;

class SiteBrand
{
    public const DEFAULT_NAME = 'EhliyetGo';

    public static function name(?SiteSetting $setting = null): string
    {
        return ($setting ?? SiteSetting::first())?->site_name ?: self::DEFAULT_NAME;
    }

    public static function text(?string $text, string $name): string
    {
        return strtr($text ?? '', ['%site_adi%' => $name]);
    }

    public static function html(?string $html, string $name): string
    {
        return self::text($html, e($name));
    }
}
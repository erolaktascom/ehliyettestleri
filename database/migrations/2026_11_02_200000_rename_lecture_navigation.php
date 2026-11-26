<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $hosts = array_filter([parse_url(config('app.url'), PHP_URL_HOST), parse_url(DB::table('seo_settings')->value('canonical_base') ?: '', PHP_URL_HOST), '127.0.0.1', 'localhost']);
        foreach(DB::table('navigation_items')->get() as $item) {
            $url = $item->url;
            $parts = parse_url($url);
            if (!$parts || (isset($parts['host']) && !in_array($parts['host'], $hosts, true))) continue;
            if (!in_array(rtrim($parts['path'] ?? '', '/'), ['/ders-notlari', '/konu'], true)) continue;
            $changes = [];
            if (rtrim($parts['path'], '/') === '/ders-notlari') $changes['url'] = preg_replace('~/ders-notlari/?(?=[?#]|$)~', '/konu', $url);
            if (in_array($item->name, ['Ders Notları', 'Konu Anlatımları'], true)) $changes['name'] = 'Konu Anlatımı';
            if ($changes) DB::table('navigation_items')->where('id', $item->id)->update($changes);
        }
        // A normal, deletable panel rule, never a hard-coded route redirect.
        if (!config('installation.fresh', false) && !DB::table('seo_redirects')->where('old_url', '/ders-notlari')->exists()) {
            DB::table('seo_redirects')->insert([
                'old_url' => '/ders-notlari', 'new_url' => '/konu', 'type' => '301', 'status' => 'Aktif',
                'sources' => json_encode([['url' => '/ders-notlari', 'match_type' => 'exact', 'ignore_case' => false]]),
                'usage_count' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
    public function down(): void
    {
        // Data edits are retained: a rollback must not overwrite later menu or redirect edits.
    }
};
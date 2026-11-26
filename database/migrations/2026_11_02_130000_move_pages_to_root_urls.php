<?php

use App\Models\Page;
use App\Support\{AutomaticSlugRedirects, PageSlug};
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $pages = Page::all();
            foreach ($pages as $page) PageSlug::validate($page->slug);
            foreach ($pages as $page) AutomaticSlugRedirects::migratePagePath($page);

            foreach (DB::table('navigation_items')->get(['id', 'url']) as $item) {
                $parts = parse_url($item->url ?? '');
                if (!$parts || isset($parts['user']) || isset($parts['pass'])) continue;
                if (isset($parts['host'])) {
                    $site = parse_url(config('app.url'));
                    if (strtolower($parts['host']) !== strtolower($site['host'] ?? '') || ($parts['port'] ?? null) !== ($site['port'] ?? null)) continue;
                }
                if (!preg_match('~^/sayfa/([a-z0-9]+(?:-[a-z0-9]+)*)/?$~', $parts['path'] ?? '', $match)) continue;
                $url = '/'.$match[1].(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
                DB::table('navigation_items')->where('id', $item->id)->update(['url' => $url]);
            }
        });
    }

    public function down(): void
    {
        // Preserve administrator-managed redirects and menu edits on rollback.
    }
};
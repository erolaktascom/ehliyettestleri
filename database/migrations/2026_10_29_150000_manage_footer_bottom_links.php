<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            // Only the extra footer Blog link is removed; header menus are unchanged.
            DB::table('navigation_items')->where('menu', 'footer')->whereNull('footer_group')
                ->where('name', 'Blog')->where('url', '/blog')->delete();

            $links = [
                ['Gizlilik Politikası', '/sayfa/gizlilik-politikasi'],
                ['Kullanım Koşulları', '/sayfa/kullanim-kosullari'],
                ['İletişim', '/iletisim'],
            ];
            foreach ($links as $index => [$name, $url]) {
                if (DB::table('navigation_items')->where('menu', 'footer')->whereNull('footer_group')->where('url', $url)->exists()) continue;
                DB::table('navigation_items')->insert([
                    'name' => $name, 'url' => $url, 'menu' => 'footer', 'footer_group' => null,
                    'audience' => 'Herkes', 'is_web' => false, 'is_mobile' => false, 'is_footer' => true,
                    'is_active' => true, 'order' => $index + 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Keep administrator edits and deleted-link choices when rolling back code.
    }
};

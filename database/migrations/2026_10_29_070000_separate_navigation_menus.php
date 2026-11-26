<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('navigation_items', fn (Blueprint $table) => $table->string('menu')->default('desktop')->index());
        DB::transaction(function () {
            foreach (DB::table('navigation_items')->orderBy('id')->get() as $row) {
                $menus = array_keys(array_filter(['desktop' => $row->is_web, 'mobile' => $row->is_mobile, 'footer' => $row->is_footer]));
                if (!$menus) $menus = ['desktop'];
                foreach ($menus as $index => $menu) {
                    $data = (array) $row;
                    unset($data['id']);
                    $data['menu'] = $menu;
                    $data['is_web'] = $menu === 'desktop';
                    $data['is_mobile'] = $menu === 'mobile';
                    $data['is_footer'] = $menu === 'footer';
                    if (!$row->is_web && !$row->is_mobile && !$row->is_footer) $data['is_active'] = false;
                    $index === 0 ? DB::table('navigation_items')->where('id', $row->id)->update($data) : DB::table('navigation_items')->insert($data);
                }
            }
            if (DB::table('navigation_items')->exists()) return;
            // Initial menus on a fresh installation; never repopulate a menu at runtime.
            $links = [['Deneme Sınavları', '/deneme'], ['Konu Testleri', '/konu-testleri'], ['Trafik İşaretleri', '/trafik-isaretleri'], ['Konu Anlatımı', '/konu'], ['Blog', '/blog']];
            foreach (['desktop', 'mobile'] as $menu) {
                $items = $links;
                if ($menu === 'mobile') array_splice($items, 2, 0, [['Animasyonlu Sorular', '/animasyonlu-sorular']]);
                foreach ($items as $order => [$name, $url]) DB::table('navigation_items')->insert([
                    'name' => $name, 'url' => $url, 'audience' => 'Herkes', 'menu' => $menu,
                    'is_web' => $menu === 'desktop', 'is_mobile' => $menu === 'mobile', 'is_footer' => false,
                    'is_active' => true, 'order' => $order + 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Separate rows retain their device flags and edits when rolling back.
        Schema::table('navigation_items', fn (Blueprint $table) => $table->dropIndex(['menu']));
        Schema::table('navigation_items', fn (Blueprint $table) => $table->dropColumn('menu'));
    }
};

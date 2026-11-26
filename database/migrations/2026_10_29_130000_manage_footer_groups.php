<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('navigation_groups', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name', 100);
            $table->unsignedInteger('order');
            $table->timestamps();
        });
        Schema::table('navigation_items', fn (Blueprint $table) => $table->string('footer_group')->nullable()->index());

        $groups = [
            'curriculum' => ['Müfredat', [
                ['Trafik ve Çevre Bilgisi', '/konu-testleri/trafik-ve-cevre'],
                ['İlk Yardım Bilgisi', '/konu-testleri/ilk-yardim'],
                ['Araç Tekniği & Motor', '/konu-testleri/arac-teknigi'],
                ['Trafik Adabı', '/konu-testleri/trafik-adabi'],
            ]],
            'corporate' => ['Kurumsal', [
                ['Hakkımızda', '/hakkimizda'], ['MEB Müfredatı', '/konu'],
                ['Sıkça Sorulan Sorular', '/hakkimizda#sss'], ['İletişim', '/iletisim'],
            ]],
            'apps' => ['Uygulamalar', [['Google Play', ''], ['App Store', '']]],
        ];
        foreach ($groups as $key => [$name, $links]) {
            DB::table('navigation_groups')->insert(['key' => $key, 'name' => $name,
                'order' => array_search($key, array_keys($groups)) + 1, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($links as $order => [$label, $url]) {
                DB::table('navigation_items')->insert([
                    'name' => $label, 'url' => $url, 'menu' => 'footer', 'footer_group' => $key,
                    'audience' => 'Herkes', 'is_web' => false, 'is_mobile' => false, 'is_footer' => true,
                    'is_active' => $url !== '', 'order' => $order + 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Preserve edited links as ungrouped footer items when rolling back.
        Schema::table('navigation_items', function (Blueprint $table) {
            $table->dropIndex(['footer_group']);
            $table->dropColumn('footer_group');
        });
        Schema::dropIfExists('navigation_groups');
    }
};

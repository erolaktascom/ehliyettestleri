<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (config('installation.fresh', false)) return;
        DB::transaction(function () {
            if (!DB::table('pages')->where('slug', 'sikca-sorulan-sorular')->exists()) {
                DB::table('pages')->insert([
                    'title' => 'Sıkça Sorulan Sorular', 'slug' => 'sikca-sorulan-sorular',
                    'short_description' => 'Platformu kullanırken merak edebileceğiniz soruların yanıtları.',
                    'content' => '<h2>Deneme sınavlarına nasıl ulaşabilirim?</h2><p>Menüdeki Deneme Sınavları bölümünden çalışmak istediğiniz sınavı seçerek başlayabilirsiniz. Soru sayısı ve süre bilgisi sınav kartında gösterilir.</p>'
                        .'<h2>Konu testlerinde süre sınırı var mı?</h2><p>Konu testleri süresizdir. İstediğiniz konuyu seçerek kendi hızınızda çalışabilirsiniz. Deneme sınavlarında ise sınava ait süre uygulanır.</p>'
                        .'<h2>Ders içeriklerini nereden okuyabilirim?</h2><p>Ders Notları bölümünden çalışmak istediğiniz konulara ulaşabilirsiniz. Trafik İşaretleri bölümünde de işaretleri inceleyebilirsiniz.</p>'
                        .'<h2>Siteyi telefonumdan kullanabilir miyim?</h2><p>Evet. Telefonunuzun veya tabletinizin internet tarayıcısından siteyi açarak çalışabilirsiniz.</p>'
                        .'<h2>Bir soru veya içerikle ilgili hatayı nasıl bildirebilirim?</h2><p>İletişim formundan bize mesaj gönderebilirsiniz. İnceleyebilmemiz için ilgili sayfanın adresini, varsa soru numarasını ve sorunun açıklamasını ekleyin.</p>',
                    'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('navigation_items')->whereIn('url', ['/hakkimizda#sss', '/sayfa/hakkimizda#sss'])
                ->update(['url' => '/sayfa/sikca-sorulan-sorular']);
        });
    }

    public function down(): void
    {
        // Keep the page and any administrator edits when rolling back code.
    }
};

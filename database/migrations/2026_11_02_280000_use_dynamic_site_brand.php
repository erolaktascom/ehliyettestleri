<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('seo_route_settings')->where('route_key', 'home')
                ->where('seo_title', 'EhliyetGo - 2026 Ehliyet Sınav Soruları ve Denemeleri')
                ->update(['seo_title' => '%site_adi% - 2026 Ehliyet Sınav Soruları ve Denemeleri']);
            // Only shipped copy is converted; edited content, links and audit dates are preserved.
            foreach ([
                ['hakkimizda', 'short_description', 'EhliyetGo vizyon ve misyonu'],
                ['hakkimizda', 'content', '<p>EhliyetGo, sürücü adaylarına modern dijital hazırlık imkanı sunar.</p>'],
                ['gizlilik-politikasi', 'content', '<p>EhliyetGo olarak kullanıcılarımızın kişisel verilerinin korunmasına büyük önem veriyoruz. Sistemimizde toplanan veriler yalnızca sınav deneyimini geliştirmek amacıyla kullanılır.</p>'],
                ['kullanim-kosullari', 'content', '<p>EhliyetGo platformunu kullanan tüm üyelerimiz kullanım şartlarını kabul etmiş sayılır. Test ve sınav içerikleri eğitim amaçlıdır.</p>'],
            ] as [$slug, $field, $text]) {
                DB::table('pages')->where('slug', $slug)->where($field, $text)
                    ->update([$field => str_replace('EhliyetGo', '%site_adi%', $text)]);
            }
        });
    }
    public function down(): void { /* Restore the pre-migration backup if needed. */ }
};
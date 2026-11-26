<?php
namespace App\Support;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
class PanelAudit {
    public const MODELS = [
        'Question'=>'Soru','Exam'=>'Deneme Sınavı','TopicTest'=>'Konu Testi','TopicCategory'=>'Soru Kategorisi',
        'Lecture'=>'Konu Anlatımı','LectureCategory'=>'Konu Kategorisi','Page'=>'Sayfa','BlogPost'=>'Blog Yazısı',
        'BlogCategory'=>'Blog Kategorisi','TrafficSign'=>'Trafik İşareti','TrafficSignCategory'=>'İşaret Kategorisi',
        'User'=>'Kullanıcı','AdminRole'=>'Rol','PremiumPlan'=>'Premium Paket','NotificationCampaign'=>'Bildirim',
        'AutomationRule'=>'Otomasyon','QuestionImport'=>'Soru Aktarımı','SeoRouteSetting'=>'Sayfa SEO Ayarları','SeoSetting'=>'SEO Ayarları',
        'SeoRedirect'=>'Yönlendirme','SeoNotFoundLog'=>'404 Kaydı','Integration'=>'Entegrasyon',
        'Advertisement'=>'Reklam','NavigationItem'=>'Menü','NavigationGroup'=>'Alt Bilgi Başlığı','SiteSetting'=>'Site Ayarları','ContactMessage'=>'İletişim Mesajı',
    ];
    private const FIELDS = [
        'title'=>'Başlık','name'=>'Ad','slug'=>'Adres','content'=>'İçerik','summary'=>'Özet','description'=>'Açıklama',
        'short_description'=>'Kısa açıklama','status'=>'Durum','is_published'=>'Yayın durumu','published_at'=>'Yayın tarihi',
        'is_active'=>'Aktiflik','order'=>'Sıralama','email'=>'E-posta','password'=>'Şifre','phone'=>'Telefon','address'=>'Adres',
        'site_name'=>'Site adı','contact_email'=>'İletişim e-postası','configuration'=>'Entegrasyon ayarları','permissions'=>'Bölüm izinleri',
        'site_description'=>'Site açıklaması','copyright_text'=>'Telif hakkı metni','social_links'=>'Sosyal medya','logo'=>'Logo','favicon'=>'Favicon',
        'role_name'=>'Rol','is_admin'=>'Panel erişimi','target_license_class'=>'Ehliyet sınıfı','question_text'=>'Soru metni',
        'options'=>'Şıklar','correct_answer'=>'Doğru cevap','explanation'=>'Açıklama','duration_minutes'=>'Süre','passing_score'=>'Baraj',
        'topic_category_id'=>'Soru kategorisi','lecture_category_id'=>'Ders kategorisi','blog_category_id'=>'Blog kategorisi',
        'traffic_sign_category_id'=>'İşaret kategorisi','author_name'=>'Yazar','code'=>'Kod','device'=>'Cihaz','audience'=>'Hedef kitle',
        'location'=>'Konum','url'=>'Bağlantı','menu'=>'Menü','footer_group'=>'Alt bilgi sütunu','sources'=>'Kaynak adresler','old_url'=>'Eski adres','new_url'=>'Hedef adres',
        'type'=>'Tür','is_read'=>'Okundu durumu','image'=>'Görsel','image_url'=>'Görsel adresi','image_path'=>'Görsel yolu',
        'file_name'=>'Dosya adı','total'=>'Toplam','ready'=>'Hazır','failed'=>'Hatalı','imported'=>'Aktarılan',
        'channels'=>'Kanallar','wait_hours'=>'Bekleme süresi','price_cents'=>'Fiyat','duration_days'=>'Gün sayısı',
        'seo_title'=>'SEO başlığı','seo_description'=>'SEO açıklaması','seo_canonical'=>'Canonical adresi','seo_index'=>'Arama motoru görünürlüğü','route_key'=>'Sayfa',
        'sitemap_sections'=>'Sitemap bölümleri','sitemap_enabled'=>'Sitemap aktifliği','sitemap_links_per_file'=>'Sitemap URL sınırı','robots_txt'=>'Robots.txt','content_rules'=>'Genel SEO kuralları','site_title'=>'SEO başlığı','meta_description'=>'SEO açıklaması',
    ];
    public static function record(Model $model, string $action, array $fields = []): void {
        $user=auth()->user();
        if (!$user || !($user->isAdministrator() || $user->isEditor())) return;
        $label=self::MODELS[class_basename($model)] ?? class_basename($model);
        // Field names only; never persist passwords, tokens, messages or integration secrets.
        $title=$model->title ?? $model->name ?? $model->file_name ?? $model->site_name ?? null;
        if ($model instanceof \App\Models\User || $model instanceof \App\Models\ContactMessage || $model instanceof \App\Models\SeoNotFoundLog) $title=null;
        $fields=array_map(fn ($field)=>self::FIELDS[$field] ?? str_replace('_',' ',$field),$fields);
        $actorId = $user->newQuery()->whereKey($user->getKey())->exists() ? $user->getKey() : null;
        AuditLog::create(['user_id'=>$actorId,'user_name'=>$user->name,'record_type'=>$label,
            'record_title'=>mb_substr(($title ? strip_tags($title).' ' : '').'#'.$model->getKey(),0,255),
            'changed_fields'=>$action.($fields ? ': '.implode(', ', $fields) : '')]);
    }
    public static function bulk(string $label, int $count): void {
        $user=auth()->user(); if (!$user || !$user->is_admin) return;
        AuditLog::create(['user_id'=>$user->id,'user_name'=>$user->name,'record_type'=>$label,
            'record_title'=>'Toplu temizleme','changed_fields'=>$count.' kayıt temizlendi']);
    }
}

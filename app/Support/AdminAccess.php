<?php

namespace App\Support;

class AdminAccess
{
    public static function groups(): array
    {
        return [
            'Genel' => ['dashboard' => 'Dashboard', 'reports' => 'Raporlar & Analiz'],
            'Sınav Yönetimi' => [
                'question-topics' => 'Soru Kategorileri', 'questions' => 'Sorular',
                'topic-tests' => 'Konu Testleri', 'exams' => 'Deneme Sınavları',
                'question-imports' => 'Toplu Soru Aktarımı',
            ],
            'Konu Anlatımı Yönetimi' => [
                'content-categories' => 'Konu Kategorileri', 'topics' => 'Konu Anlatımları',
            ],
            'Trafik İşaretleri' => [
                'traffic-sign-categories' => 'İşaret Kategorileri', 'traffic-signs' => 'Trafik İşaretleri',
            ],
            'Blog Yönetimi' => [
                'blog-categories' => 'Blog Kategorileri', 'blog-posts' => 'Blog Yazıları',
            ],
            'Sayfa Yönetimi' => ['pages' => 'Sayfalar'],
            'Kullanıcı Yönetimi' => [
                'users' => 'Üyeler', 'staff-users' => 'Yönetim Ekibi',
                'admin-roles' => 'Rol ve İzinler',
            ],
            'Premium Yönetimi' => ['premium-plans' => 'Premium Paketleri'],
            'İletişim & Bildirimler' => [
                'notification-campaigns' => 'Bildirimler',
                'automation-rules' => 'Otomasyon Kuralları',
            ],
            'SEO Yönetimi' => [
                'seo-settings' => 'Genel Ayarlar', 'seo-redirects' => 'Yönlendirmeler',
                'seo-not-found-logs' => '404 Kayıtları', 'seo-verification' => 'Doğrulama Ayarları',
                'sitemap-settings' => 'Sitemap Ayarları',
            ],
            'Site Yönetimi' => [
                'settings' => 'Site Ayarları', 'media' => 'Medya Yönetimi', 'navigation-items' => 'Menü Yönetimi',
                'advertisements' => 'Reklam Alanları', 'integrations' => 'Entegrasyonlar',
                'audit-logs' => 'İşlem Geçmişi',
                'contact-messages' => 'İletişim Mesajları',
            ],
        ];
    }

    public static function modules(): array
    {
        return array_merge(...array_values(self::groups()));
    }

    public static function editorModules(): array
    {
        // Managing accounts or roles could grant the editor administrator access.
        return array_diff_key(self::modules(), array_flip(['admin-roles', 'staff-users', 'users']));
    }

    public static function defaultEditorPermissions(): array
    {
        return ['question-topics', 'questions', 'question-imports', 'exams', 'topic-tests',
            'content-categories', 'topics', 'pages', 'traffic-sign-categories', 'traffic-signs',
            'blog-categories', 'blog-posts'];
    }
}

<?php

namespace App\Support;

class IntegrationCatalog
{
    public static function all(): array
    {
        return [
            'smtp' => [
                'name' => 'E-posta Gönderimi (SMTP)', 'provider' => 'SMTP', 'ready' => true,
                'description' => 'Şifre sıfırlama, planlı bildirimler ve otomasyon e-postaları için sunucu bağlantısı.',
                'instructions' => 'E-posta sağlayıcınızın SMTP bilgilerini girin. Şifreleme alanına tls (genellikle 587) veya ssl (genellikle 465) yazın. Gönderen adı Site Ayarlarındaki site adından otomatik alınır. Kaydetmek test e-postası göndermez.',
                'fields' => [
                    'host' => ['label' => 'SMTP Sunucusu', 'type' => 'text', 'max' => 255],
                    'port' => ['label' => 'Port', 'type' => 'text', 'max' => 5],
                    'encryption' => ['label' => 'Şifreleme (tls / ssl)', 'type' => 'text', 'max' => 3],
                    'username' => ['label' => 'Kullanıcı Adı', 'type' => 'text', 'max' => 255],
                    'password' => ['label' => 'Şifre / Uygulama Şifresi', 'type' => 'password', 'max' => 1024],
                    'from_address' => ['label' => 'Gönderen E-posta Adresi', 'type' => 'email', 'max' => 255],

                ],
            ],
            'google_login' => [
                'name' => 'Google ile Giriş / Kayıt',
                'provider' => 'Google',
                'description' => 'Kullanıcıların Google hesaplarıyla giriş ve kayıt yapması için ayarlar.',
                'ready' => true,
                'fields' => [
                    'client_id' => ['label' => 'İstemci Kimliği (Client ID)', 'type' => 'text', 'max' => 255],
                    'client_secret' => ['label' => 'İstemci Gizli Anahtarı (Client Secret)', 'type' => 'password', 'max' => 1024],
                ],
                'callback_path' => '/auth/google/callback',
            ],
        ];
    }

    public static function get(string $key): array
    {
        abort_unless(isset(self::all()[$key]), 404);
        return self::all()[$key];
    }
}

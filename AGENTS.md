# Proje geliştirme kuralları

- Değişiklik yapmadan önce kökteki `CODEX_GUIDE.md` dosyasını okuyun.
- Panelde bir bölüm eklerken, kaldırırken veya kapatırken rotaları ve `app/Support/AdminAccess.php` kaydını birlikte güncelleyin. Menü ve Rol ve İzinler ekranı bu ortak kaynaktan üretilmelidir; ayrı sabit listeler oluşturmayın.
- Yeni bölümler Editör için varsayılan olarak kapalı kalmalıdır; Yönetici izin ekranından açar.
- Bölüm kaldırılırken kayıtlı rol izinlerindeki eski anahtarı da veri migration'ı ile temizleyin. Ayrıntılar `CODEX_GUIDE.md` bölüm 2.5 içindedir.
- `php artisan test` başarılı olmadan işi tamamlanmış saymayın. `AdminModuleRegistryTest` uyumsuzluklarını testi gevşeterek veya yeni istisnalar ekleyerek gizlemeyin.

- Entegrasyonlar panelden serbest eklenmez; hizmet tanımı ve özel alanları `IntegrationCatalog` içinde, pasif veritabanı kaydı yeni migration ile hazırlanır. Gerçek bağlantı tamamlanmadan etkinleştirmeyi açmayın; ayrıntılar `CODEX_GUIDE.md` hazır entegrasyon kataloğu bölümündedir.

- Panelde mevcut kaydı düzenlerken Kaydet sonrasında aynı formda kalın; listeye yönlendirmeyin. Ortak başarı mesajını bir kez gösterin. Yeni kayıt oluşturmanın listeye dönüşü korunur.

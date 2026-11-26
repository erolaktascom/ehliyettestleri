# ehliyettestleri - MEB E-Sınav Hazırlık Portalı

Bu proje, `https://ehliyet.sworkz.com.tr/` platformunun birebir tasarımı, kullanıcı akışları, sınav motoru ve yönetim paneli mimarisi analiz edilerek **Laravel 12 + Blade + Livewire 3 + Tailwind CSS + Alpine.js** mimarisiyle geliştirilmiştir.

---

## 🚀 Hızlı Başlangıç

Projeyi tek tıkla başlatmak için:
- `C:\xampp\htdocs\ehliyettestleri\start.bat` dosyasına çift tıklayın.

Veya terminalden:
```bash
cd C:\xampp\htdocs\ehliyettestleri
php artisan serve --port=8000
```
Tarayıcınızdan açın: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## 🔑 Giriş Bilgileri

Üye girişi için [üye giriş sayfasını](http://127.0.0.1:8000/login), yönetici girişi için [yönetim panelini](http://127.0.0.1:8000/admin) açın. Yönetici hesabı ilk kurulumda belirlenir.

---

## 🛠️ Dahili Modüller ve Özellikler

1. **Ana Sayfa:**
   - 2026 MEB Müfredatına Tam Uyumlu rozeti
   - 50 Soru / 45 Dakika / 70 Geçme Puanı özet kutuları
   - "Hızlı Deneme Başlat" ve "Konulara Çalış" aksiyonları
   - Yeni nesil animasyonlu sorular vitrin kartı

2. **Deneme Sınavları (`/deneme`):**
   - 45 Dakikalık gerçek sınav formatı
   - **İnteraktif Sınav Motoru (`/sinav/oturum/{id}`):**
     - Geri sayım sayacı (`mm:ss`), son 5 dakikada acil kırmızı durumuna geçme, süre bitiminde otomatik tamamlama
     - Soru haritası (1-N butonları: cevaplananlar mavi, boşlar gri, aktif soru vurgulu)
     - Sayfa yenilenmeden arka planda anlık AJAX cevap kaydı
     - Sınav tamamlama onay modalı
   - **Sonuç ve Detaylı Çözüm Ekranı (`/sinav/oturum/{id}/sonuc`):**
     - Geçti / Kaldı rozetleri
     - 100 üzerinden puan dairesi (70 baraj puanı)
     - Doğru, Yanlış, Boş sayıları
     - Soru bazında kullanıcının cevabı, doğru cevap ve **Çözüm Açıklaması**

3. **Konu Testleri (`/konu-testleri`):**
   - Kategoriler: Trafik ve Çevre, İlk Yardım, Araç Tekniği, Trafik Adabı, Animasyonlu Sorular

4. **Animasyonlu Sorular (`/animasyonlu-sorular`):**
   - Hareketli video ve senaryo tabanlı soru çözme arayüzü

5. **Trafik İşaretleri (`/trafik-isaretleri`):**
   - Flaş kartlarla interaktif öğrenme (`/ogren/trafik-isaretleri/{kategori}`)
   - "Biliyorum" ve "Tekrar Listesine Ekle" butonlarıyla canlı ilerleme çubuğu (%0-%100)
   - Çoktan seçmeli trafik levha testleri

6. **Konu Anlatımları (`/konu`):**
   - MEB standartlarında dijital ders notları
   - Kategori tamamlanma yüzdesi takibi
   - Ders okuma sayfası, "Tamamlandı olarak işaretle", sonraki derse geçiş

7. **Blog (`/blog`):**
   - Kategori filtreleri, yazar profili (`/yazar/mehmet-aktas`), WhatsApp paylaşım butonu

8. **İletişim & Hakkımızda (`/iletisim`, `/hakkimizda`):**
   - Spam korumalı iletişim formu (mesajlar doğrudan Admin paneline düşer)

9. **Livewire Yönetim Paneli (`/admin`):**
   - Deneme Sınavları, Sorular, Konu Testleri, Trafik Levhaları, Dersler, Blog Yazıları, Gelen Mesajlar ve Kullanıcı Yönetimi


## Tasarım dosyaları ve şifre yenileme

- CSS yerelde Vite + Tailwind 3 ile derlenir: `pnpm install --frozen-lockfile` ve `pnpm run build`. Node 24 + pnpm 11 kullanılır. `pnpm-lock.yaml` kilit dosyası korunur.
- Dağıtım paketine `public/build` dizini eklenir. Sunucuda Node bulunması gerekmez; Git/Plesk dağıtımında bu dosyalar derleme adımıyla üretilir veya hazır paketten aktarılır. Kurulum derlenmiş dosyaları kontrol eder.
- Alpine, Livewire tarafından sağlanır; ayrıca CDN veya ikinci Alpine başlatması eklenmez. Filament kullanılmaz.
- Üye/yönetici girişinden “Şifremi unuttum” aynı güvenli akışa gider. E-posta için panelde Entegrasyonlar → SMTP alanına sağlayıcının host, port, kullanıcı, parola, TLS/SSL ve gönderici adresi girilip etkinleştirilir. SMTP kapalıysa sıfırlama gönderimi yapılmaz. Bağlantılar süreli ve tek kullanımlıktır.

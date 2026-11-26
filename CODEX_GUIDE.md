# ehliyettestleri - Codex Geliştirici Rehberi

Bu dosya, projeyi **Codex**, **Cursor**, **VS Code** veya başka bir yapay zeka aracında geliştirirken tam uyum ve sıfır hata sağlamak için hazırlanmıştır.

---

## 1. Proje Özeti ve Teknoloji Yığını
- **Framework:** Laravel 12 (kurulu sürüm: 12.70.0, PHP 8.2+)
- **Frontend / UI:** Laravel Blade + **Livewire 3** + **Tailwind CSS** + Alpine.js
- **Veritabanı:** MySQL 8.0+ / MariaDB 10.3+. Yerel site 3 Kasım 2026 itibarıyla XAMPP MariaDB 10.4.32 üzerinde `127.0.0.1:3306 / ehliyetgo_local` kullanır (`DB_CONNECTION=mysql`). Bağlantı bilgileri `.env` içindedir; parola dokümana yazılmaz. SQLite artık canlı veri kaynağı değildir; eski dosyalar proje dışında yedektedir.
- **Yönetici Girişi:**
  - URL: `http://127.0.0.1:8000/admin/login`

---

## 2. Kesin Kurallar ve Mimari İlkeler (Kritik)

1. **KESİNLİKLE MODAL / POPUP KULLANILMAZ:**
   - Admin panelindeki tüm modüller (22 modül) tam sayfa form mimarisine (`<Entity>Form.php`) taşınmıştır.
   - Yeni eklemeler: `/admin/{modul}/create`
   - Düzenlemeler: `/admin/{modul}/{id}/edit`
   - Her formda tek kaydet düğmesi bulunur. Üstte kaydet varsa altta veya soru seçimi gibi alt bileşenlerde ikinci kaydet eklenmez. Üstte geri dönüş bağlantısı bulunan formlarda alttaki tekrar İptal/Listeye Dön bağlantısı kullanılmaz.
   - Listeleme sayfalarındaki butonlar modal açmaz; doğrudan tam sayfa rotaya yönlendirir.
   
2. **Zengin Metin Editörü:**
   - Sorularda, konularda ve blog yazılarında `<x-rich-editor>` bileşeni kullanılır.

3. **Dinamik Soru Şıkları:**
   - Soru formunda şıklar dinamik olarak artırılabilir (`+`) veya azaltılabilir (`-`).

4. **Test Bütünlüğü:**
   - Yapılan her geliştirmeden sonra `artisan test` çalıştırılmalı ve tüm testler yeşil kalmalıdır. 2 Kasım 2026 içerik SEO ayarları güncellemesi sonrası: **324 test, 5594 assertion** (artisan test başarılı).
   - Sınav ekranındaki JavaScript değişikliklerinde `node --test tests/js/exam-answer-actions.test.cjs` de çalıştırılmalıdır (8 test).

5. **Panel Bölümü ve İzin Listesi Birlikte Güncellenir:**
   - Tek bölüm kaynağı `app/Support/AdminAccess.php` dosyasıdır. Menü, izin kutuları ve sunucudaki erişim denetimi aynı kaydı kullanır.
   - Bölüm eklerken `AdminAccess::groups()` kaydını ve `admin.{bolum}` / `admin.{bolum}.{islem}` adlı rotalarını birlikte ekleyin. Bütün panel rotaları `admin` middleware ile korunmalıdır. Menü/açılış rotası parametresiz GET olmalıdır.
   - Yeni bölüm Editör için varsayılan olarak kapalıdır. Mevcut rollere otomatik izin eklemeyin; Yönetici izin ekranından seçer.
   - Bölümü kaldırırken veya erişime kapatırken rotalarını, ortak bölüm kaydını, varsa varsayılan izin kaydını ve ilgili bağlantıları birlikte kaldırın. Yalnızca menüyü gizlemek yeterli değildir.
   - Kaldırılan bölümün anahtarını mevcut `admin_roles.permissions` dizilerinden yeni veri migration'ıyla temizleyin; izin sayısını JSON dizisinden hesaplayın (ayrı sayaç sütunu yoktur). Eski migration dosyalarını değiştirerek mevcut veritabanının güncelleneceğini varsaymayın. Aynı anahtarı ilgisiz yeni bir bölüm için yeniden kullanmayın.
   - `AdminModuleRegistryTest` panel rotalarını izin kaydıyla iki yönde karşılaştırır; unutulan ekleme/kaldırma ve eksik middleware testleri başarısız yapar. Testi atlamayın; uyumsuzluğun kaynağını düzeltin.

---

## 3. Çalıştırma ve Test Komutları

```bash
# Proje Dizinine Git
cd C:\xampp\htdocs\ehliyettestleri

# Geliştirme Sunucusunu Başlatma
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000

# Önbellekleri Temizleme
C:\xampp\php\php.exe artisan optimize:clear

# Tüm Testleri Çalıştırma
C:\xampp\php\php.exe artisan test
```

---

## 4. Dizin Haritası
- **Admin Livewire Bileşenleri:** `app/Livewire/Admin/`
- **Admin Blade Şablonları:** `resources/views/livewire/admin/`
- **Ön Yüz Controller & Views:** `app/Http/Controllers/`, `resources/views/`
- **Veritabanı Modelleri:** `app/Models/`
- **Test Dosyaları:** `tests/Feature/` (AdminPanelRoutesTest, AdminLivewireCrudTest, EhliyetGoFeatureTest vb.)

## 5. 28 Ekim 2026 Sistem İncelemesi

- Soru Konuları paneli, soru formu, konu testleri ve öğrenci ekranlarının ortak kategori kaynağı `TopicCategory` / `topic_categories` tablosudur. Animasyonlu Sorular, İlk Yardım gibi sıradan bir ana kategoridir; `is_animated` işareti kullanılmaz. Medya türü kategori seçiminden bağımsızdır. Eski `question_topics` tablosu ve yabancı anahtarları 250000 migration ile kaldırılmıştır.
- Sorular ekranı soru havuzudur: kategori, soru metni, medya, şıklar ve cevap yönetilir. Test seçimi ve sıra alanı burada bulunmaz. Deneme ve konu testi formlarındaki ortak ManagesTestQuestions bileşeni havuzdan ekleme/çıkarma ve sürükle/oklarla sıralamayı yönetir. Kaydetme, test bilgileri ile soru ilişkilerini transaction içinde yazar; soru sayısı seçimden hesaplanır. Konu testine yalnız aynı kategorideki sorular eklenebilir; test kategorisini değiştirmek paylaşılan soruların kategorisini değiştirmez.
- Sınav oturumları `ExamAttemptPolicy` ile korunur: üyelerde kullanıcı kimliği, misafirlerde oturum çerezi eşleşmelidir. Okuma, cevaplama, bitirme ve sonuç işlemleri aynı kontrolü kullanır.
- Süresi dolmuş veya bitmiş sınava cevap eklenemez. Soru oturuma ait olmalı, seçilen şık soruda bulunmalıdır. Sonuçlar sınav bitmeden açılamaz.
- Cevap kaydetme ve sonuçlandırma veritabanı transaction'ı içinde çalışır. Tekrar sonuçlandırma tamamlanmış oturumu değiştirmez.
- Ortak tarayıcı kayıt akışı `resources/views/partials/exam-answer-actions.blade.php` içindedir. İstekler sıralıdır; bitirme kayıtları bekler; başarısız kayıtlar yeniden denenebilir. Boş tekrar soruları cevaplanmış sayılmaz.
- `AdminMiddleware`, Livewire güncelleme isteklerinde yeniden uygulanır. Admin yetkisi kaldırılmış açık sayfalar işlem yapamaz.
- Normal/admin giriş formlarında eski CSRF belirteciyle gönderim (419) güvenli biçimde toparlanır: geçerli oturumu olan kullanıcı paneline yönlendirilir; misafire yeni giriş formu ve açıklama gösterilir. Şifre eski girdilere kaydedilmez, CSRF denetimi devam eder. `LoginSessionRecoveryTest` gerçek CSRF kontrolünü etkinleştirerek bu akışları sınar.
- Soru formunda şık silinirken doğru cevabın metni korunur. Doğru şık silinirse yeni doğru cevap seçilmesi gerekir; tekrar eden şık anahtarları reddedilir.
- Kullanıcı/yönetici düzenlemede şifre boş bırakılabilir; ehliyet sınıfı alanı profil ekranıyla aynı uzunluk sınırını kullanır. Yönetici kendi yetkisini düzenleme formundan kaldıramaz.
- İletişim mesajları `is_read` alanını kullanır. Detay sayfası `/admin/contact-messages/{id}` adresindedir; açılan mesaj okundu işaretlenir.
- Taslak sınav, konu testi, blog ve ders içeriklerinin doğrudan adresle erişimi engellenir. Trafik işareti test listeleri ve ders sayaçları yayım durumunu dikkate alır.
- Admin sınav sayacı `is_published` kullanır. Öğrenci kategori ilerlemesi gerçek soru kategorilerinden hesaplanır; yapay 7 soru/5 doğru varsayımları kaldırılmıştır. Performans ortalamasına yalnızca tamamlanmış oturumlar katılır.
- Ders ve içerik kategorileri yalnız lectures / lecture_categories tablolarından yönetilir. Adres değişince mevcut otomatik SEO yönlendirme akışı korunur; eski tabloya kopya yazılmaz.
- Panelde yalnızca Yönetici ve Editör rolleri bulunur. admin_roles.permissions JSON dizisi editörün bölüm izinlerini tutar; izin sayısı bu diziden hesaplanır; ayrı permissions_count sütunu yoktur. Yönetici /admin/admin-roles ekranından kutuları seçip kaydeder. AdminAccess bölüm listesinin tek kaynağıdır; User::canAccessAdminRoute(), AdminMiddleware, Livewire kalıcı middleware ve menü aynı izinleri kullanır. Hesap ve rol yönetimi yalnızca Yönetici’ye açıktır. İzni olmayan editör /admin/access ekranına yönlendirilir.
- Yazar profili adresindeki slug gerçek yayımlanmış yazarlardan çözülür; isim ve baş harfler dinamiktir. Mehmet AKTAŞ profili `/yazar/mehmet-aktas` adresindedir.
- Kullanıcı talebiyle önceki yazar atıflı 3 blog, 1 işlem kaydı ve eski yedekten sahipliği doğrulanan 1 soru aktarımı Mehmet AKTAŞ hesabına aktarılmıştır. Aktarım ayrıca denetim kaydına yazılmış, işlem öncesi veritabanı yedeklenmiştir. Soru aktarımı düzenlenirken ilk oluşturan kullanıcı korunur.

- Test-soru ilişkileri exam_question ve question_topic_test tablolarındadır; position alanı her teste özeldir. Aynı soru birden fazla testte farklı sırada kullanılabilir. Eski questions.exam_id, topic_test_id ve order sütunları kaldırılmıştır; tüm kayıt ve aktarım akışları yalnız çoktan çoğa ilişkiler ve pivot position alanını kullanır.
- ExamAttempt.question_ids oturum başlarken seçilen soru kimliklerini sırasıyla saklar; testin soru listesi değiştirilince mevcut oturumun ve sonucunun soru üyeliği değişmez. Bu alan soru metni veya cevap içeriğinin kopyası değildir. Mevcut ilişkiler ve oturum listeleri migration ile taşınmıştır.

### İçeriği ekleyen kişi

- Soru, deneme sınavı, konu testi, blog ve ders içeriklerinde last_edited_by / last_editor_name son gerçek düzenlemeyi yapan panel kullanıcısını tutar. TracksPublication::recordContentEdit() içerik değişiminde ve ortak soru listesi kaydında kullanılır. Sağdaki yayın/tarih bölümünde Son Düzenleyen gösterilir; ilk oluşturma, değişikliksiz kaydetme, sayaç ve yalnız yayın durumu değişikliği kişiyi değiştirmez. Ekleyen değişmez. Taslak içerik düzenlemeleri de kişiye atanır. Kimliği bilinmeyen geçmiş işlemler geriye dönük bir kullanıcıya atanmaz. Kullanıcı silinirse adı korunur; panel kullanıcısı olmadan yapılan gerçek değişiklikte eski düzenleyen yeni işlemin sahibi gibi gösterilmez. Bu alanlar form/fillable girdisi değildir. LastContentEditorTest beş içerik türünü, sahipliğin korunmasını, sahte düzenleyen reddini ve silinen kullanıcıyı doğrular.

- Ekleyen bilgisi gösterilen bütün içerik formlarında (soru, deneme sınavı, konu testi, ders konusu, sayfa ve trafik işareti) bu bilgi sağdaki kategori/yayın/parametre bölümünde yer alır; formun üstünde ayrı bir Ekleyen şeridi gösterilmez. Blogda yalnız Editör, kategori formlarında ise Ekleyen göstermeme tercihi korunur.

- 28 Ekim 2026 kullanıcı talebiyle ekleyen bilgisi boş olan 592 mevcut kayıt (503 soru dahil) Mehmet AKTAŞ hesabına atanmıştır. Bu, geçmiş işlem sahibinin tespiti değil açıkça istenen geriye dönük atamadır. İşlem öncesi SQLite yedeği alınmış ve denetim geçmişine adetleriyle kaydedilmiştir. Yeni kayıtlarda gerçek oturum kullanıcısı otomatik kaydedilmeye devam eder.

- Paneldeki sorular, soru konuları, deneme sınavları, konu testleri, dersler, içerik kategorileri, sayfalar, blog yazıları/kategorileri ve trafik işaretleri/kategorileri `TracksCreator` ortak model trait’ini kullanır. `created_by` kullanıcı ilişkisi, `creator_name` ise ekleme anındaki isimdir. Ekleyen gösterilen listeler ve formlar ortak `admin.creator-name` / `admin.creator-info` Blade bileşenlerini kullanır. Kullanıcı tercihiyle kategori liste/formlarında Ekleyen gösterilmez; blog yazılarında yalnız Editör gösterilir. Soru, deneme sınavı ve konu testlerinde Ekleyen görünür. Görünümden kaldırılması kayıtlı sahiplik bilgisini silmez.
- Yeni kayıt oturum açmış Yönetici veya Editör adına otomatik oluşturulur. Bu alanlar form girdisi/fillable değildir; düzenleme ilk ekleyeni değiştiremez. Kullanıcı silinirse ilişki null olur, ekleyen adı korunur. Eski kayıtlarda kanıtlanmış ekleyen bilgisi yoksa “Kayıt yok” gösterilir; düzenleyen kişiye mal edilmez. Blog author_name ilk ekleyenin editör adını taşır; elle değiştirilemez. Ayrıntılar Blog editörü bölümündedir.
- Yeni içerik modülü eklerken creator sütunlarını migration ile ve `TracksCreator` trait’ini modele ekleyin, liste/formda ortak bileşenleri kullanın. Toplu insert/upsert model olaylarını çalıştırmaz; yeni aktarım akışları ekleyeni açıkça ve güvenilir işlem kullanıcısından kaydetmelidir. Ders/kategori uyumluluk tabloları yerine panelin esas `Lecture` / `LectureCategory` kayıtları takip edilir.
- `ContentCreatorTest` Yönetici/Editör oluşturma, ilk ekleyenin korunması, sahte ekleyen reddi, eski kayıtlar, silinen kullanıcı ve blog editörünün ilk ekleyenle eşleşmesini doğrular.

### Taslak ve yayın durumu

- Paneldeki bütün mevcut kayıt düzenleme formları Kaydet sonrasında aynı ekranda kalır ve ortak başarı mesajını bir kez gösterir. Reklam, menü, kategori, sayfa, trafik işareti, rol izinleri, kullanıcı, premium paket, bildirim, otomasyon, yönlendirme ve aktarım formları da bu kurala dahildir. Tekrar kaydetmek aynı kaydı günceller. Yalnız yeni kayıt oluşturma işlemi listeye yönlendirir.

- Boolean yayın alanına bağlı select seçeneklerinde true/false metin değerleri ve başlangıç selected niteliği kullanılır. 0/1 seçenekleri Alpine ilk yüklemesinde boolean ile eşleşmez ve taslak kayıtta yanlış seçim gösterebilir. Deneme, konu testi, blog ve ders formları bu eşlemeyi kullanır; PublicationControlsTest iki durumu da doğrular.
- Eski aktarımın active soru durumları, 2026_10_29_010000 migration ile Yayında değerine dönüştürüldü. Mevcut taslaklar, ekleyen bilgisi ve tarihler korunur. Yeni soru kayıtları yalnız Yayında/Taslak değerleri kullanmalıdır.

- Taslak sorular hem taslak hem yayındaki deneme/konu testlerine eklenebilir ve test içinde saklanabilir. Soru taslak diye testin kaydedilmesi engellenmez; soru durumu otomatik değiştirilmez. Havuz her iki durumu da gösterir. Öğrenciye yeni oturumda yalnız yayındaki sorular verilir; geçersiz kimlik, tekrar eden soru ve konu kategorisi kontrolleri devam eder.

- Sorular, deneme sınavları, konu testleri, blog yazıları ve ders konuları listelerinde ortak `admin.publication-toggle` bileşeni kullanılır. Durum düğmesi `toggleActive` çağrısıyla Yayında/Taslak arasında geçiş yapar; beş listede de durum filtresi vardır. Formlarda Yayın Durumu seçimi bulunur. Sorular `status` (Yayında/Taslak), diğer dört esas model `is_published` kullanır.
- Ders yayın durumu yalnız esas Lecture kaydında tutulur; eski Topic modeli ve tablosu kaldırılmıştır. Blog ilk kez listeden yayımlanırken boş `published_at` doldurulur; mevcut yayın tarihi korunur.
- Yeni sınav/konu testi oturumlarının soru listesi yalnız yayındaki sorulardan oluşur; hiç yayında soru yoksa oturum açılmaz. Seçilen kimlikler ve toplam aynı sorgudan alınır. Başlamış oturumların soru listesi ve sonuçları sonradan taslağa alma nedeniyle değiştirilmez. Kaydedilen sorular ve yeni yanlış soru tekrarları taslak soruları göstermez.
- Taslak blog/ders doğrudan adresle açılmaz; taslak sınav/test başlatılmaz. Panel bölüm izinleri Livewire durum işlemlerinde de geçerlidir. `PublicationControlsTest` durum geçişleri, filtreler, öğrenci görünürlüğü, oturum sürekliliği ve geri alınmış editör iznini sınar.

### Panel bildirimleri

- Bildirim kampanyası hedef kitlesi `target_groups` JSON alanında saklanır. `NotificationAudience` kayıtlı hesapları Herkes / Üyeler (Premium olmayan) / Premium Üyeler olarak sorgular; premium_until süresi dolmuş hesaplar Üyeler grubuna geçer. Birden fazla grup seçilebilir, Herkes diğer seçimleri kaldırır. Kişi sayısı seçim sırasında ve kaydederken sunucuda hesaplanır; elle girilmez ve teslimat sayısı değildir. Yönetim hesapları da kayıtlı hesap sayısına dahildir.
- Ziyaretçi aboneliği bulunmadığından ziyaretçi seçimi pasiftir. Eski serbest hedef kitleler korunur; tanınmayan eski hedefler düzenlenirken açıkça yeni seçim istenir, otomatik olarak Herkes yapılmaz. Yeni kampanya Taslak başlar; yalnız Şimdi gönder / Planlandı kaydedildiğinde execution_enabled açılır. Eski örnek kampanyalar migration ile gönderime açılmaz.

### Planlı bildirim ve otomasyon yürütücüsü

- `notifications:process` komutu `routes/console.php` içinde her dakikaya planlanır. `NotificationProcessor`, kampanyanın zamanı geldiğinde güncel hedef kitleyi seçer ve `notification_deliveries` tablosuna kişi/kanal başına benzersiz kayıt yazar. İşleyici en fazla 100 kanal kaydını bir çalışmada gönderir. Bu kalıcı gönderim tablosu kendi kuyruğudur; ayrı `queue:work` gerektirmez. Gönderim başlamadan taslağa alma veya iptal çalışır; gönderim başladıktan sonra içerik değiştirilemez. Bekleyen iletiler iptal edilebilir, başlamış ileti geri alınamaz.
- İki kanal hazırdır: `in_app` üyenin `/bildirimler` sayfasına ve üstteki zil menüsüne gerçek kayıt ekler; `email` paneldeki SMTP bağlantısını kullanır. Okundu işlemi POST/CSRF ve hesap sahipliği kontrolünden geçer. E-postanın Gönderildi olması SMTP sunucusunun kabulünü ifade eder, okunma/teslim garantisi değildir. Log/array mail sürücüsüne düşerek sahte başarı yazılmaz.
- SMTP, IntegrationCatalog içindeki hazır bir entegrasyondur; migration pasif kayıt oluşturur. Entegrasyonlar → E-posta Gönderimi (SMTP) bölümünden sunucu, port, tls/ssl, kullanıcı adı, şifre ve gönderen bilgileri kaydedilir. Şifre şifreli saklanır ve Livewire snapshot içine geri konmaz. Kaydetmek test e-postası göndermez. Gerçek sağlayıcıyla bağlantı testi kullanıcı SMTP bilgilerini girdikten sonra yapılabilir.
- Durumlar Taslak / Sırada / Planlandı / Gönderiliyor / Gönderildi / Hata / İptal / Alıcı Yok şeklindedir. Gönderildi panelden elle seçilemez. Tarih/saat config(app.timezone) ile yorumlanır. Her kanalın sonucu kampanya/kural düzenleme ekranında görünür. SMTP isteği sonrası kesinti veya hata sonucu belirsizse otomatik yeniden gönderim yapılmaz; sağlayıcı kaydı kontrol edilmelidir. Yalnız SMTP denemesi öncesi başarısız olan kayıtlar panelden tekrar sıraya alınabilir. Başarılı kanallar tekrar gönderilmez.
- Otomasyon tetikleyicileri `registered`, `exam_passed`, `inactive`; bekleme saatle belirlenir. Başlık/mesajda {ad} kullanıcı adı olur. Otomasyon yönetim hesaplarını kapsamaz. Yeni üyelik ve başarı olayları etkinleştirme tarihinden itibaren taranır; hareketsizlik yalnız etkinleştirmeden sonraki ziyaret/kayıt zamanından başlar. Aynı olay/ziyaret dönemi bir kez bildirilir. Pasife alma bekleyen kayıtları iptal eder, yeniden açma geçmişi yeniden oynatmaz. Kaydetme last_checked_at değerini değiştirmez; gerçek tarama ve tetikleme last_checked_at / last_triggered_at alanlarına ayrı yazılır.
- Üye ziyaret zamanı TrackMemberActivity ile en fazla 5 dakikada bir kaydedilir; profil updated_at ve denetim geçmişi değiştirilmez. Siteye dönen üyeye henüz gönderilmemiş hareketsizlik hatırlatması iptal edilir.
- Yerelde `C:\xampp\php\php.exe artisan schedule:work --no-interaction` açık kalmalıdır; uygulama yeniden başlatıldığında gerektiğinde tekrar başlatılır. Hostingte PHP ve proje yolunu gerçek yollarla değiştirerek dakikalık cron tanımlayın: `* * * * * cd /path/to/ehliyetgo && /path/to/php artisan schedule:run >> /dev/null 2>&1`. Birden fazla sunucuda ortak veritabanı ve cache kilit deposu kullanılır. Bildirim/otomasyon listelerinde son işleyici kontrolü görünür; üç dakikadan eski kontrol uyarı gösterir.
- NotificationProcessingTest gerçek gönderim yapmadan zamanlama, güncel kitle, tekrar engeli, iptal, SMTP yapılandırması/gizlilik/hatalar, tetikleyiciler, gecikme, eski verilerin gönderilmemesi ve bildirim sahipliğini sınar.

- Başarı, hata ve uyarı mesajlarının tek görünüm kaynağı admin.flash-messages Blade bileşenidir. Her tam sayfa admin Livewire görünümünün kök elemanı içinde bir kez çağrılır; böylece yönlendirme sonrası ve sayfa yenilenmeyen Livewire işlemlerinde çalışır. Layout, slot içeren Livewire sayfalarında mesajı tekrar göstermez; yalnız klasik Blade sayfaları için yedek çağrı yapar. Yeni sayfalarda ikinci bir session mesaj bloğu eklemeyin.
- AdminFlashMessageTest bütün kayıtlı panel bölümlerinde mesajların bir kez göründüğünü, yönlendirmeli sınav kaydını ve yönlendirmesiz durum değişimini doğrular.

### Ayrı geliştirme gerektiren mevcut eksikler

- Video/embed kaynaklarının tarayıcıda oynatılabilmesi kaynak sitenin iframe iznine ve dosyanın erişilebilir olmasına bağlıdır. Sayfa adresi yerine sağlayıcının embed adresi kullanılmalıdır.
- Web Push (tarayıcı aboneliği/servis çalışanı) ve SMS kanalları henüz bağlı değildir. Site içi bildirim Web Push değildir. Gerçek SMTP sunucu bilgileri henüz girilmediğinden harici e-posta teslimatı yerelde doğrulanmadı; site içi gönderim ve e-posta taşıyıcı akışı izole testlerde doğrulanır.
- Rol listesi, bölüm kutuları ve izin kaydetme akışı yerel tarayıcıda doğrulandı. Kapsamlı yük ve harici servis teslimat testi yapılmadı.


### İçerik incelemesi sonrası tarih, sayaç ve medya kuralları

- Soru, deneme, konu testi, blog ve ders modelleri TracksPublication ile ilk yayın tarihini first_published_at alanına kaydeder. Taslakta boş kalır; ilk yayımlamada dolar, tekrar yayımlama değiştirmez. created_at oluşturulma, updated_at son kayıt değişikliğidir. 28 Ekim kullanıcı talebiyle yayında olup ilk yayın tarihi boş olan eski kayıtlara created_at başlangıç tarihi atanmıştır; bu gerçek geçmiş yayın tarihinin tespiti değildir. Mevcut yayın tarihleri korunmuştur. İlk yayın tarihi boş bir taslak yayımlandığında işlem zamanı kaydedilir.
- Listelerde yalnız content_updated_at doluysa “Son Güncelleme”, aksi durumda “Yayınlanmış” görünür. Beş düzenleme formunda ilk yayın tarihi ve varsa son güncelleme gösterilir. Kullanıcı tercihiyle oluşturulma tarihi bu form/listelerde ve tarih ipuçlarında gösterilmez; görünümde yayın tarihi yerine created_at kullanılmaz; eski kayıtların kullanıcı talebiyle bir defalık tarih tamamlaması migration içindedir. Henüz yayımlanmamış kayıtta ilk yayın tarihi boştur. Blog görüntülenme sayacı updated_at tarihini değiştirmez. content_updated_at yalnız gerçek içerik alanı veya test-soru üyeliği/sırası değiştiğinde yazılır. Aynı içerikle Kaydet, şıkların eşdeğer JSON dönüşümü, görüntülenme sayacı, yayın durumu ve otomatik soru sayacı güncelleme sayılmaz. Eski updated_at kayıtları sistem/aktarım işlemleriyle karıştığından korunmuş ama içerik güncellemesi olarak aktarılmamıştır.
- Admin deneme/konu testi sayaçları yayındaki ve taslak soruları ayrı gösterir. Konu testleri süresizdir; panelde süre girdisi ve topic_tests.duration_minutes sütunu yoktur. Öğrenci deneme kartları ve ana sayfa bilgileri yayındaki soru sayısı, duration_minutes ve passing_score üzerinden üretilir. Geçmiş sonuç rozetleri kayıtlı is_passed değerini kullanır.
- Taslak içerikte canlı bağlantı kutusu gösterilmez. Blog yeni kayıt düğmesi yayına çıkma vaadi vermeyen “Yazıyı Kaydet” metnini kullanır.
- Soru editörünün Video / Embed Ekle alanı bağlantı, iframe veya video kodu kabul eder. QuestionMedia bilinen YouTube/Vimeo bağlantılarını dönüştürür; diğer kaynakların embed adreslerini ve yerel/uzak video dosyalarını destekler. Script, olay nitelikleri, srcdoc ve uygunsuz URL şemaları saklanmaz. Öğrenci ekranları biçimli soru metnini aynı temizleme katmanından geçirerek gösterir. Eski görsel/video bağlantıları question_text HTML içeriğine taşınmıştır; kategori veya metindeki “Animasyonlu” kelimesinden sahte oynatıcı oluşturulmaz.
- Quill video ve nativeVideo biçimlerini destekler. Quill nesnesi Alpine reaktif proxy içinde tutulmamalıdır; aksi halde seçim/offset hataları oluşur. Livewire değerini editöre yüklerken clipboard API sessiz kaynakla kullanılır.
- ContentReviewFixesTest yayın tarihinin korunması, blog okuma tarihi, taslak/yayın sayaçları, süresiz konu testi, dinamik deneme bilgileri, medya temizliği, kayıt/açılış ve öğrenci oynatıcılarını doğrular. Başlamış sınavın içerik/cevap kopyası ve ileri tarihli blog zamanlaması bu değişikliğin kapsamına dahil değildir.

- Panel listelerindeki çözülme, okunma, görüntülenme, kullanım ve içerik adedi sütunlarında değer yalnız sayı olarak gösterilir. Birim açıklaması sütun başlığındadır; sayıya deneme/sınav/kez/istek/Yazı/Ders gibi ekler yazılmaz. Yayında/taslak gibi farklı durumları ayıran etiketler korunur.


### SEO yönlendirmelerinin çalışması

- ApplySeoRedirects global middleware, rota eşleşmesinden önce yalnız GET/HEAD isteklerinde aktif seo_redirects kurallarını uygular. Böylece artık rotası olmayan eski adresler de 301/302 veya 410 dönebilir. Panel, Livewire, giriş ve oturum/işlem adresleri kapsam dışıdır; POST/form gövdeleri başka adrese aktarılmaz.
- SeoRedirectRules site içi yolları ve bu sitenin tam kaynak adreslerini normalleştirir; hedef olarak site içi yol veya http/https adresi kabul edilir. Kaynaklar sources JSON dizisinde url, match_type, ignore_case alanlarıyla tutulur; bir kayıtta en fazla 20 kaynak olabilir. old_url ilk kaynakla eşlenir; sources boş eski kayıtlar tek bir tam eşleşme olarak okunur. Tam eşleşme, İçerir, Şununla başlar, Şununla biter ve Düzenli ifade desteklenir. Sorgulu tam eşleşme, sorgusuz tam eşleşme, sonra desenler önceliklidir; aynı öncelikte eski kayıt ve kaynak sırası kullanılır. Desenler yalnız çözümlenmiş URL yoluna uygulanır. Yerel hedefte kaynak sorgu değerleri korunur, hedefte yazılan değerler önceliklidir; harici hedefe yalnız açıkça tanımlanan hedef sorgusu gönderilir.
- Aynı kaynak/eşleşme çifti (harf duyarsızlık dahil), güvensiz URL şeması, kontrol karakteri ve belirlenebilen yönlendirme döngüleri formda reddedilir. Dinamik desen döngüleri çalışma anında ziyaret edilmiş adres ve 20 adım sınırıyla durdurulur. Eski/geçersiz kayıtlar ve döngüler ziyaretçiye hata üretmeden atlanır. Çalışan her yönlendirme usage_count ve last_used_at alanlarını günceller; pasif kayıtlar çalışmaz.
- Paneldeki yönlendirmeler çalışır; herkese açık içerik adreslerinin slug değişiklikleri de otomatik 301 kaydı oluşturur (aşağıdaki otomatik yönlendirme kuralları). SeoRedirectTest aktif/pasif, 301/302, HEAD/POST, sorgu eşleşmesi, dış hedef, döngü, korunan yollar, formdan ekleme/düzenleme ve sayaçları doğrular.

- SEO kural türü 410 (Gone), hedefe yönlendirmeden HTTP 410 ve Türkçe kaldırılmış sayfa görünümü döndürür. Hedef alanı formda gizlenir, sunucuda kullanılmaz ve boş saklanır; new_url sütunu uyumluluk için boş metin alır. Location başlığı gönderilmez. GET/HEAD kullanımı sayılır; HEAD gövdesi boştur. Pasif kurallar ve korunan yollar için mevcut davranış sürer. 301/302 zinciri 410 ile sonlanabilir. 410’dan 301/302’ye geçişte hedef yeniden zorunludur.

- Form Türkçedir; yalnız 301, 302 ve 410 sunulur ve sunucuda kabul edilir. Çoklu kaynak ekleme/kaldırma, her kaynakta harf duyarlılığı, tek hedef ve Etkin/Devre Dışı seçimi vardır. Regex ayraçsız yazılır (örnek ^/eski/(.*)$); $1…$99 hedefte yakalanan grupları kullanır. Regex PCRE eşleşme/derinlik sınırlarıyla çalışır; geçersiz veya sınırı aşan desenler yönlendirme yapmaz. Yakalanan değerler URL kodlanır ve üretilen hedef yeniden doğrulanır. SeoRedirectPatternsTest çoklu kaynak kaydetme/açma/arama/silme, beş eşleşme türü, harf duyarlılığı, regex grupları, öncelik, geçersiz desenler, döngüler ve eski kayıt uyumluluğunu sınar.


### Bağımsız masaüstü ve mobil menüler

- navigation_items.menu alanı desktop/mobile/footer değerlerini taşır. Her satır yalnız bir menüye aittir. Eski ortak kayıtlar 2026_10_29_070000 migration ile isim, URL, kitle, durum ve sıra korunarak ayrı satırlara bölünür. Eski is_web/is_mobile/is_footer sütunları kaldırılmıştır; tek kaynak menu ve footer_group alanlarıdır. Hiçbir konumda görünmeyen eski öğe pasif korunur. Migration öncesi SQLite yedeği alınmıştır.
- Menü Yönetimi içinde Masaüstü Menü, Mobil Menü ve mevcut alt bilgi bağlantıları için Alt Bilgi Menüsü ayrı adreslerde açılır: /admin/navigation-items/desktop, /mobile, /footer. Her birinin /create ve /{id}/edit sayfası vardır. Eski kök adres masaüstünü açar, eski düzenleme adresi kaydın menüsünü belirler. Liste arama/silme ve düzenleme sorguları seçili menüye sınırlandırılır; menu/itemId Livewire Locked alanlarıdır. Sıra başlangıcı yalnız aynı menüden hesaplanır.
- Bunlar mevcut navigation-items modülünün alt sayfalarıdır; izin kaynağı AdminAccess içindeki Menü Yönetimi kaydıdır. Ayrı veya varsayılan editör izni eklenmez. Bütün alt rotalar admin.navigation-items.* olarak adlandırılır ve mevcut admin middleware tarafından korunur.
- Site üst menüsü ve mobil açılır menünün içerik bağlantıları artık NavigationItem::visibleMenus() ile veritabanından ayrı okunur. Sıra, aktiflik ve Herkes/Üyeler/Misafirler kitlesi uygulanır. Boşaltılmış veya kapatılmış menü sabit linklerle yeniden doldurulmaz. İlk kurulum bağlantıları yalnız migration sırasında boş tabloya eklenir. Mobil kullanıcı özeti, Genel Bakış/Hesap Ayarları ve giriş/çıkış kontrolleri ayrı sistem öğeleri olarak kalır. Alt bilgi menüsü üç sütunun başlık ve bağlantılarını, ayrıca gruplandırılmamış alt satır bağlantılarını yönetir. En alt yasal/iletişim bağlantıları da aynı ekranın En Alt Menü konumundan yönetilir.
- Menü URL'leri / ile başlayan yerel yol veya http/https adresi olabilir. Betik şemaları, protokolsüz dış adresler ve kontrol karakterleri formda reddedilir; eski güvensiz kayıtlar sitede gösterilmez. NavigationMenusTest veri taşıma, ayrı CRUD/arama/sıralama, yanlış menüde işlem reddi, site görünürlüğü, kitle, boş menü ve bölüm izinlerini doğrular.


### Hazır entegrasyon kataloğu

- Panel serbest entegrasyon oluşturmaz veya silmez. /admin/integrations/create rotası ve create/delete/toggleActive işlemleri kaldırılmıştır. Entegrasyon tanımı ve hizmete özel alanlar app/Support/IntegrationCatalog.php içinde geliştirilir; her yeni tanımın tekil integration_key kaydı yeni migration ile eklenmelidir. Yeni kayıt varsayılan pasiftir. Katalog dışı eski veriler silinmez, panelde düzenlenmez. Bölüm ve editör izni mevcut AdminAccess integrations kaydıdır.
- Google ile Giriş / Kayıt (google_login) Laravel Socialite 5.31 ile uygulanmıştır; ready=true yalnız kodun bağlantıya hazır olduğunu belirtir. Veritabanındaki is_active varsayılan ve mevcut kurulumda false kalır. Client ID ve Client Secret tamamlanmadan etkinleştirme reddedilir. Kayıt işlemi Google’a istek göndermez veya kimlik bilgilerinin geçerliliğini doğrulamaz. Bağlantıyı kullanıcı panelden açar. Gerçek Google kimlik bilgileri bulunmadığından canlı hesap testi henüz yapılmamıştır.
- Servise özel alanlar katalogdaki fields tanımından çizilir ve doğrulanır. Katalog dışı form anahtarları reddedilir. integrationId Locked alanıdır; sağlayıcı adı, anahtarı ve bağlantıya hazır olma bilgisi tarayıcı girdisinden alınmaz. Bir entegrasyonu hazır ilan etmeden önce gerçek sürücü/akış, gerekli alan doğrulamaları ve davranış testleri tamamlanmalıdır; ready bayrağı tek başına hizmeti bağlamaz.
- configuration alanı encrypted:array cast ile şifreli saklanır; configuration ve eski settings model serileştirmesinde gizlidir. Mevcut gizli anahtar form/Livewire snapshot içine yüklenmez; boş parola alanı eski değeri korur, kaydetme sonrası girilen parola alanı temizlenir. Laravel APP_KEY değişimi için şifreli kayıtların anahtar yönetimi dikkate alınmalıdır.
- IntegrationCatalogTest serbest ekleme/silmenin kapalı olması, pasif Google, şifreli kayıt, gizli anahtarın tarayıcıya geri verilmemesi/korunması, eksik bilgiyle aktivasyon ve bilinmeyen alan reddi, izin ve Livewire yetki iptali kontrollerini yapar. Panel rota testi artık oluşturma adresi yerine migration ile hazırlanmış Google ayar kaydını kullanır.


### Google ile giriş ve kayıt akışı

- GoogleLogin panelde şifreli tutulan configuration değerlerinden her istek için yeni Socialite GoogleProvider üretir. stateless kullanılmaz; session state, PKCE, 10 dakika süre, tek kullanımlık akış kaydı ve ayar/geri dönüş adresi parmak izi kontrol edilir. Entegrasyonun işlem sırasında kapanması veya bilgilerinin değişmesi akışı iptal eder. HTTP istemcisi sabit Google uçlarına, TLS doğrulaması açık, yönlendirme kapalı ve zaman aşımıyla gider. Erişim/yenileme tokenları veritabanına kaydedilmez, OAuth hata mesajları veya izleri loglanmaz; yalnız istisna sınıfı yazılır.
- /auth/google/redirect ve /auth/google/callback guest ve istek sınırlamasıyla korunur. /auth/google/link GET/POST mevcut hesabı şifreyle eşleştirir; POST CSRF ve ayrı 5/dakika sınıra sahiptir. /auth yolları SEO yönlendirmelerinin dışındadır. GoogleLogin::callbackUrl() yalnız config('app.url') üzerinden üretilir; panelde gösterilen adres ve token isteğindeki adres aynıdır. Sunucu yayına taşınırken APP_URL gerçek HTTPS site adresi olmalıdır; kullanıcı Google Cloud Web uygulaması OAuth istemcisinde bu geri dönüş adresini birebir tanımlar. Google uygulaması test modundaysa test kullanıcılarını Google tarafına ekler.
- Giriş/kayıt sayfalarındaki ortak partial yalnız aktif ve gerekli bilgileri dolu entegrasyonda Google ile Devam Et bağlantısını gösterir. Önceki işlevsiz Google düğmesi kaldırılmıştır. Google girişleri doğrulanmış e-posta ve kalıcı sub kimliğini gerektirir. users.google_id tekildir, gizlidir ve fillable değildir. Eşleşmiş hesap sub üzerinden bulunur; Google e-posta değişimi mevcut yerel hesabı veya profil bilgilerini otomatik değiştirmez.
- Yeni Google hesabı yalnız Öğrenci olarak, password=null ve email_verified_at dolu oluşturulur. Mevcut aynı e-postalı hesap otomatik birleştirilmez; 10 dakikalık sunucu oturumunda kimlik tutulur ve mevcut EhliyetGo parolasıyla açık eşleştirme gerekir. Farklı Google kimliğine bağlı hesap üzerine yazılmaz. Yönetici/Editör hesapları Google ile giriş/eşleştirme kullanmaz; mevcut parola girişleri korunur. Kullanıcı girişinde oturum kimliği yenilenir.
- Google ile açılan şifresiz hesap, Hesap Ayarları bölümünde ilk şifresini oluşturabilir. Yerel şifresi olan kullanıcıda mevcut şifre doğrulaması zorunlu kalır. Böylece sonradan Google kapatıldığında kullanıcı e-posta/şifreyle devam edebilir. Şifre oluşturma/güncelleme auth ve CSRF arkasındadır.
- GoogleLoginTest gerçek Socialite sağlayıcısını Guzzle sahte HTTP yanıtlarıyla sınar: state/PKCE, kayıt/tekrar giriş, pasif/eksik ayarlar, izin reddi, token hatası, doğrulanmamış e-posta, süre/tekrar kullanma/ayar değişimi, mevcut hesap parolası, hesap çakışması, yönetici erişimi reddi, brute-force sınırı, ilk parola ve SEO istisnası. Canlı Google hesabına veya gerçek müşteri bilgilerine test sırasında bağlanılmaz.
- Composer güvenlik denetiminde bulunan mevcut symfony/mime v7.4.19, v7.4.22 güvenlik yamasına yükseltilmiştir. Son denetimde advisory bulunmamıştır. composer.json ve composer.lock Socialite ve bağımlılıklarını içerir.
- Resmî kaynaklar: https://laravel.com/docs/12.x/socialite ve https://developers.google.com/identity/protocols/oauth2/web-server .


### Masaüstü ve mobil reklam alanları

- advertisements.device desktop/mobile değerlerini taşır. Masaüstünde Header, LeftBanner ve RightBanner; mobilde yalnız Header kabul edilir. 2026_10_29_100000 migration mevcut Header kayıtlarını içerik ve durumunu koruyarak mobil için ayrı satıra kopyalar; Sidebar kayıtlarını masaüstü RightBanner konumuna taşır. Diğer eski konumlar silinmez fakat yeni site alanlarında gösterilmez. Migration öncesi SQLite yedeği alınmıştır.
- /admin/advertisements/desktop ve /mobile bağımsız liste, /create ve /{id}/edit adreslerini kullanır. Eski kök adres masaüstünü, eski düzenleme adresi kaydın cihazını açar. Liste, arama, silme ve düzenleme sorguları cihaza sınırlandırılır; device/adId Locked alanlarıdır. Bu alt rotalar mevcut advertisements modülünün iznini kullanır; yeni izin anahtarı eklenmez.
- Advertisement::slots() yalnız aktif, geçerli cihaz/konum ve içerik taşıyan reklamları seçer. Aynı cihaz/konumda önce oturum durumuna uygun kitleye özel reklam, yoksa Herkes reklamı seçilir; aynı kitlede son güncellenen kayıt, eşitlikte büyük id gösterilir. Header site üst menüsünün altında; sağ ve sol bannerlar ana içeriğin dışındaki ayrı sütunlarda yer alır. Yan alanlar yalnız 1024px ve üzerindeki ekranlarda görünür; mobilde yer ayırmaz. Header da bu eşikte ayrı mobil/masaüstü kaydından yüklenir.
- site-ad bileşeni medya sorgusu ve Alpine x-if ile yalnız geçerli cihazın içeriğini yükler. HTML/JS reklam kodu sandbox iframe içinde gösterilir; allow-same-origin verilmez. Dış reklam sağlayıcılarının kodlarının bu izolasyonla uyumluluğu sağlayıcı bazında ayrıca doğrulanmalıdır. İçerik yalnız code alanından gösterilir; kodu boş reklam gizlenir. Tıklama hedefi HTML/JavaScript reklam kodunda tanımlanır; ayrı Hedef URL alanı ve liste sütunu yoktur. Kullanılmayan target_url sütunu kaldırılmıştır. Önerilen boyutlar masaüstü Header 728x90, mobil Header 320x100 ve yan banner 160x600'dür.
- AdvertisementDevicesTest cihazlara özel CRUD/arama, konum doğrulaması, yanlış cihazdan düzenleme reddi, görünürlük/öncelik, kod izolasyonu, URL doğrulaması, mevcut Header aktarımı ve editör bölüm iznini doğrular.

- Reklamlarda audience alanı ilk sürümde tek everyone/members/guests değeri tutuyordu. Güncel çoklu seçim yapısı aşağıdaki 30 Ekim bölümündedir. Mevcut kayıtlar 2026_10_29_110000 migration ile everyone varsayılanını alır; içerik ve aktiflik değişmez. Üye auth()->check() ile giriş yapmış kullanıcıdır, ziyaretçi oturumsuz kullanıcıdır. Kitle kontrolü Advertisement::slots() sorgusunda sunucuda yapılır; uygun olmayan reklam kodu veya görseli HTML'ye eklenmez. Pasif/boş kitleye özel reklam Herkes yedeğini engellemez. Form kitleyi doğrular ve yükler; her cihazın listesinde kitle sütunu ve filtresi vardır. AdvertisementAudienceTest bütün konumlarda üye/ziyaretçi ayrımını, Herkes yedeğini, oturum geçişini, CRUD/filtre/validasyonu ve eski veri varsayılanını sınar.

- AdminEditStayTest düzenleme formlarında arka arkaya iki kaydın yönlendirme yapmadığını, veritabanında aynı kaydı güncellediğini ve tek başarı mesajı gösterdiğini doğrular. Kullanıcı ve yönetici formlarında başarılı kayıt sonrası password alanı temizlenir; sonraki boş parola mevcut şifreyi korur. Yeni formlarda düzenleme sonrası listeye yönlendirme eklemeyin.

### İletişim mesajları doğrulaması

- İletişim sayfasındaki Livewire ContactForm ve klasik POST /iletisim mesajı contact_messages tablosuna okunmamış olarak kaydeder. Panelde ad/e-posta/konu/mesaj araması ve okundu filtresi vardır; detay açıldığında is_read true olur. E-posta ile Yanıtla mailto bağlantısıdır, sunucudan e-posta göndermez. ContactMessageFlowTest izole test veritabanında gönderim, doğrulama, honeypot, arama, okundu durumu, silme, erişim izni ve HTML kaçışını uçtan uca kontrol eder; gerçek kullanıcı mesajlarını değiştirmez.

- Reklam formunda ayrı Banner Görsel Adresi alanı yoktur; yeni reklam içeriği yalnız HTML/JavaScript koduyla düzenlenir. Eski image alanındaki görseller, code boşsa HTML koduna taşınmış ve image sütunu kaldırılmıştır. Yeni kod içinde görsel gerekiyorsa img etiketi kullanılır.


### 29 Ekim 2026 panel incelemesi: 5, 6, 7, 10 ve 11

- RecordNotFound middleware son yanıt 404 olduğunda GET/HEAD isteklerini seo_not_found_logs tablosunda adres bazında sayar. Sorgu parametreleri ve korunan panel/oturum yolları kaydedilmez. İlk/son görülme tarihi tutulur; 410 ayrı kalır. 2026_10_29_120000 migration tekrar eden eski adresleri sayaçlarıyla birleştirir ve tekil indeks ekler. Migration öncesi yedek: C:/xampp/ehliyetgo-backups/before-panel-tracking-20261029.sqlite.
- PanelAuditObserver, PanelAudit::MODELS içinde tanımlı esas panel modellerinin ekleme, gerçek değiştirme ve silme işlemlerini oturumdaki Yönetici/Editör adına kaydeder. Denetim kaydı işlem türü, kayıt başlığı/kimliği ve değişen alan adlarını içerir; eski/yeni değerler, parolalar ve entegrasyon anahtarları yazılmaz. Değişikliksiz kayıt, sayaçlar ve uyumluluk modelleri çift kayıt oluşturmaz. Kullanıcı silinirse işlemdeki adı korunur. Soru üyeliği/sırası ve toplu günlük temizliği ayrıca kaydedilir. Yeni panel modeli MODELS listesine eklenmelidir; toplu query/insert/upsert model olaylarını atladığı için açık PanelAudit çağrısı gerekir.
- SiteSetting site adı üst menü, alt bilgi, giriş ve panel markasında; iletişim e-postası, telefon ve adres iletişim sayfasında kullanılır. Paylaşılan veriler yalnız mevcut istekte önbelleklenir; ayar kaydı aynı istekte bu önbelleği temizler.
- TracksSlugRedirects / AutomaticSlugRedirects blog yazısı ve kategorisi, sayfa, ders ve ders kategorisi, soru konusu ve trafik işareti kategorisinin herkese açık GET adreslerini takip eder. Ders kategorisi değişince alt ders adresleri de yönlendirilir. Model değişikliği ve 301 kaydı aynı transaction içindedir. A→B→C değişiminde mevcut otomatik kayıtlar doğrudan C'ye gider. Yönlendirmeler panelinden silinen kayıt yeniden üretilmez; eski adres başka içerik/kural tarafından kullanılmıyorsa 404 olur. Elle tanımlanan kurallar korunur; etkin eski adresi yeni slug olarak kullanmak çakışma hatası verir. Otomatik kuralın içeriği/durumu panelden değiştirilirse otomatik sahipliği kaldırılır, sonraki slug işlemleri bu tercihi ezmez. Deneme/konu testi başlatma yolları POST işlemidir, herkese açık GET detay sayfaları olmadığından bu kapsama alınmaz.
- BlogPost::visible ve isContentPublished yayında işareti yanında published_at zamanını denetler. Gelecek tarihli yazı Planlandı olarak görünür; listeler, kategori sayaçları, doğrudan içerik, ilgili yazılar ve yazar profilinde zamanı gelene kadar gizlidir. Görünürlük saat kontrolüyle çalışır, cron gerekmez. Vakti gelen yayının first_published_at değeri ilk sorguda planlanan saat olarak tamamlanır; içerik güncellemesi veya sahte düzenleyen oluşturmaz. Europe/Istanbul saat dilimi kullanılır. Taslağa alma görünürlüğü yine kapatır.
- PanelCompletionTest otomatik 404, denetim ve gizli veri koruması, silinen işlem sahibi, tekrarlı ayar kaydı, slug zinciri/silme/çakışma, alt ders adresleri ve planlı yayın saatini izole veritabanında doğrular. Kullanıcının ertelediği diğer inceleme maddeleri bu kapsamda değiştirilmemiştir.


### Alt bilgi sütunlarının yönetimi

- Menü Yönetimi > Alt Bilgi Menüsü (/admin/navigation-items/footer) ekranında Müfredat, Kurumsal ve Uygulamalar sütunlarının başlıkları düzenlenir. İlk kurulumda NavigationGroup tablosunda curriculum/corporate/apps sütunları hazırlanır. 30 Ekim dinamik sütun güncellemesiyle normal sütunlar eklenebilir/silinebilir; apps ayrı sekmeden yönetilir. Başlık kaydı aynı sayfada tek başarı mesajı gösterir ve PanelAudit tarafından izlenir.
- NavigationItem.footer_group bağlantının sütununu belirler; null telif hakkının yanındaki En Alt Menü konumudur. Ayrı ek bağlantı satırı kullanılmaz. Sütun seçimi yalnız footer menüsünde kabul edilir. Formda ad, URL, sütun, sıra, hedef kitle ve aktiflik yönetilir. Liste sütun adını veritabanından gösterir. Masaüstü/mobil üst menüler etkilenmez; aynı navigation-items izni ve mevcut rotalar kullanılır.
- Ön yüzde başlıklar ve bağlantılar veritabanından gelir. Bağlantılar sütun içinde order/id ile sıralanır; pasif, kitleye uymayan veya güvensiz adresli bağlantılar görünmez. Silinen bağlantılar çalışma sırasında yeniden oluşturulmaz. Uygulamalar sütunu bağlantıları düğme biçiminde gösterir.
- 2026_10_29_130000 migration eski sabit sekiz içerik bağlantısını üç sütun yapısına taşır. Gerçek adresi olmayan Google Play/App Store kayıtları boş URL ile pasif hazırlanır; ilk aktarımda pasiftir; sonraki 160000 migration kullanıcı isteğiyle bağlantısız görünümü açar. Mevcut navigation_items kayıtları korunur. Öncesinde C:/xampp/ehliyetgo-backups/before-footer-groups-20261029.sqlite yedeği alınmıştır.
- FooterNavigationTest başlıkların tekrarlı kaydını/ön yüze yansımasını, denetim kaydını, bağlantı ekleme-düzenleme-silme, sıralama/kitle/pasiflik, URL/sütun doğrulamasını ve editör izinlerini kontrol eder. Layoutlar Tailwind CDN kullanır; bu Blade değişiklikleri Vite derlemesi gerektirmez.


### Site kimliği, sosyal medya ve telif ayarları

- Site Ayarları (mevcut settings izni) logo, favicon, site_description, copyright_text ve social_links alanlarını yönetir. Tek kayıt ve tek kaydet düğmesi kullanılır; kaydetme aynı sayfada kalır. 2026_10_29_140000 migration mevcut alt bilgi açıklamasını ve © {year} {site_name}. Tüm hakları saklıdır. şablonunu taşır. Geçmiş logo/favicon değerleri korunur. Yedek: C:/xampp/ehliyetgo-backups/before-site-branding-20261029.sqlite.
- Logo PNG/JPG/WEBP, en çok 2 MB ve 4096×4096; favicon PNG/ICO, en çok 512 KB kabul eder. SVG yüklenmez. WithFileUploads doğrulamasından sonra public diskin branding dizinine rastgele dosya adıyla kaydedilir. Form yol girdisi kabul etmez. Değiştirme/kaldırma sonrasında yalnız yönetilen branding dosyası temizlenir; DB hatasında yeni dosyalar geri alınır. public/storage bağlantısı artisan storage:link ile oluşturulmuştur; hostinge taşınırken bu bağlantı ve storage yazma izni gerekir.
- Logo ön yüz üst/alt bilgide ve panel/giriş markasında, favicon ön yüz/panel/giriş head bölümünde kullanılır. Görsel kaldırılırsa mevcut marka ve favicon.svg yedeği kullanılır. Site açıklaması alt bilgiye yansır; SEO meta_description boşsa meta açıklamasına da yansır. Copyright düz metindir, {year} ve {site_name} sunucuda değiştirilir; boş bırakılırsa metin gösterilmez.
- Instagram, TikTok, YouTube, Facebook ve X için yalnız kullanıcı adı girilir; profil adresi sunucuda platformun sabit alan adından üretilir. Boş/geçersiz kayıtların simgesi gösterilmez; genel sosyal ağ ana sayfalarına sabit bağlantılar kaldırılmıştır. Sosyal adresler, açıklama ve telif metni Blade tarafından kaçırılır; HTML çalıştırılmaz. PanelAudit bu alanların değişen adlarını kaydeder.
- SiteBrandingTest tekrarlı kaydetme, metinlerin ön yüze yansıması/kaçışı, boş sosyal alanlar, logo/favicon yükleme-değiştirme-kaldırma, fiziksel dosya temizliği, uygunsuz dosya/URL reddi ve settings iznini doğrular. Yerelde public/storage üzerinden görsel 200 image/png yanıtı ayrıca kontrol edilmiştir.


### En alt menü (telif hakkının yanı)

- Gizlilik Politikası, Kullanım Koşulları ve İletişim sabit Blade bağlantıları değildir; footer menüsünün footer_group=null kayıtlarından çizilir. Panelde Alt Bağlantılar sekmesinden (/admin/navigation-items/footer-bottom) yönetilir. Ekleme, düzenleme, silme, sıra, hedef kitle ve aktiflik ayrı liste/form rotalarıyla yürür; mevcut navigation-items izni kullanılır.
- 2026_10_29_150000 migration yalnız eski gruplandırılmamış footer /blog Blog kaydını kaldırır, üç en alt bağlantıyı kayıtlı aynı URL yoksa ekler. Üst menü ve sütun içindeki bağlantılara dokunmaz. Önce C:/xampp/ehliyetgo-backups/before-footer-bottom-20261029.sqlite yedeği alınmıştır. Migration geri alınırken yöneticinin düzenlemeleri korunur.
- Ön yüzde ayrı Blog/ek bağlantı satırı kaldırılmıştır. Ayraç yalnız görünen bağlantıların arasına eklenir; liste boşsa menü ve ayraçlar görünmez, silinen kayıtlar otomatik geri gelmez. FooterNavigationTest en alt bağlantıların düzenlenmesi, sıralama, kitle/pasiflik, tamamen silinmesi ve migration kapsamını doğrular.


### Bağlantısız mağaza alanları

- Kullanıcı isteğiyle 2026_10_29_160000 migration, adresi boş mevcut mağaza kayıtlarını görünür yapar ve Google Play adını Play Store olarak düzenler. Yalnız footer/apps konumunda boş URL kabul edilir; ön yüzde bu öğeler href içermeyen span olarak görünür. URL eklenirse bağlantı olur, silinirse yeniden bağlantısız görünür; pasif kayıt yine gizlenir. Diğer menülerde URL zorunludur ve dolu adresler güvenlik doğrulamasından geçer. FooterNavigationTest bu geçişleri doğrular.

- Mağaza alanları resources/views/components/store-badge.blade.php bileşeninde siyah, Türkçe Apple/Google Play rozetleri olarak çizilir. İkonlar yerel SVG kodudur; dış görsel servisine bağlı değildir. URL boşken span, doluyken a kullanılır. 170000 migration referansa uygun App Store → Play Store sırasını kaydeder; sıralama sonradan panelden değiştirilebilir. Diğer özel uygulama adları düz metinli rozet olarak kalır.

- Sosyal medya girdisi kullanıcı adıdır (isteğe bağlı başında @ ve çevresinde boşluk kabul edilir, kayıtta temizlenir). social_links JSON alanında kullanıcı adları saklanır; SiteSetting::socialUrl sabit profil kökünden güvenli URL üretir. YouTube ve TikTok adresinde @ otomatik eklenir. Yeni formda tam URL ve yol/sorgu girdileri reddedilir. Eski tanınan profil URL’leri form açılışında kullanıcı adına dönüşür ve sonraki kayıtta bu biçimde yazılır; dönüştürülemeyen eski kanal-ID adresleri otomatik değiştirilmez. Mevcut yerel sosyal kayıtlar bu değişiklik sırasında boştu. SiteBrandingTest beş platformu, normalizasyonu, URL oluşumunu ve eski kayıt uyumluluğunu doğrular.

- İletişim sayfasındaki sabit çalışma saatleri kaldırıldı. Telefon bilgi kartı yalnız SiteSetting.phone doluysa gösterilir (boşluklardan oluşan değer de gizlenir). 180000 migration eski örnek +90 850 123 45 67 numarasını temizler; seeder artık telefon oluşturmaz. Diğer kayıtlı numaralar korunur. Mesaj gönderenin isteğe bağlı telefon girdisi bu karttan ayrıdır. PanelCompletionTest ayarlar kaydından numara ekleme/kaldırmanın sayfaya yansımasını doğrular.


### Panelden yönetilen kurumsal sayfalar ve SSS

- 190000 migration Sıkça Sorulan Sorular kaydını başlangıç soru-cevaplarıyla pages tablosuna ekler; mevcut aynı slug varsa içerik ve yayın durumunu değiştirmez. /hakkimizda#sss menü bağlantıları /sayfa/sikca-sorulan-sorular adresine taşınır. SSS düzenleme dahil tüm işlemler mevcut pages izniyle yürür.
- Sayfaların adresi /{slug} biçimindedir (örnek: /gizlilik-politikasi); başlık, açıklama ve HTML içeriği paneldeki Page kaydından okunur, yalnız published kayıtlar açılır. /hakkimizda doğrudan paneldeki Hakkımızda içeriğini gösterir. Görüntülenme sayacı updated_at tarihini değiştirmez. Panel liste ve form bağlantıları kök adresi kullanır.
- 2 Kasım 2026: 2026_11_02_130000 migration mevcut /sayfa/{slug} adreslerine kayıt sahipliği olan 301 SEO yönlendirmeleri ekler; menülerdeki yerel eski adresleri sorgu ve parça bilgilerini koruyarak taşır. Sayfa içerikleri ve yayın/güncelleme tarihleri değiştirilmez. Yönlendirme panelden silinirse eski adres 404 olur; kalıcı /sayfa/ rota takma adı yoktur. Sonraki slug değişikliklerinde mevcut otomatik yönlendirmeler yeni kök adrese güncellenir.
- PageSlug doğrulaması sistem rotalarının ilk bölümünü, korunan yolları ve public altındaki dosya/dizin adlarını sayfa adresi olarak engeller. /{slug} rotası en sondadır; /hakkimizda yönetilen sayfa olarak izinlidir. RootPageUrlTest kök adresleri, taslak erişimini, eski adres migration ve silmesini, slug değişimini, menü/tarih korumasını ve adres çakışmalarını doğrular. Taşıma öncesi yedek: C:/xampp/ehliyetgo-backups/before-root-pages-20261102-104844.sqlite.
- ManagedPagesTest SSS listeleme/düzenleme/taslak erişimi, menü bağlantısı, Hakkımızda adresi, okuma sırasında tarihin korunması ve migration tekrarında mevcut düzenlemelerin korunmasını doğrular. Yedek: C:/xampp/ehliyetgo-backups/before-faq-page-20261029.sqlite.


### Blog yayın tarihi gösterimi

- Blog listesinde tek Tarih sütunu bulunur. Kullanıcının tercihiyle Yayınlanmış değeri sabit first_published_at alanından gelir; gerçek içerik değişmişse Son Güncelleme gösterilir, ilk yayın tarihi ipucunda korunur. Blog formunun tarih özetinde İlk Yayınlanma ve varsa Son Güncelleme birlikte gösterilir. Planlı yayında Planlanan Yayın ayrıca gösterilir. Düzenlenebilir published_at alanı bu geçmiş özetinden ayrıdır.
- first_published_at geçmiş kaydı tarih düzenlemesiyle değiştirilmez; ön yüz blog tarihi ve liste sıralaması published_at kullanır. TracksPublication isteğe bağlı içerik alanlarında null/boş metin dönüşümünü gerçek değişiklik saymaz. Yayın tarihinin elle değiştirilmesi Son Güncelleme ve Son Düzenleyen oluşturur; değişmeyen tarih girdisi mevcut saniyeleri korur ve sahte güncelleme oluşturmaz. PanelCompletionTest form kaydı, yeniden açılış, liste/ön yüz tarihi, ilk yayın kaydının korunması ve gerçek içerik güncellemesini birlikte sınar.

### Sayfaların yayın ve güncelleme geçmişi

- Kullanıcı tercihiyle Sayfalar listesinde Ekleyen ve Görüntülenme sütunları, düzenleme formunda Ekleyen alanı gösterilmez. Kayıtlı sahiplik ve görüntülenme verileri korunur.
- Page modeli TracksPublication kullanır; yayın koşulu status=published değeridir. İlk yayımlama first_published_at alanını doldurur, tekrar yayımlama değiştirmez. Gerçek içerik veya yayın tarihi değişikliği content_updated_at ve son düzenleyeni kaydeder; değişikliksiz Kaydet, yalnız yayın durumu ve view_count değişimleri içerik güncellemesi sayılmaz.
- Liste ortak Tarih hücresinde Yayınlanmış veya Son Güncelleme, formda İlk Yayınlanma ve varsa Son Güncelleme gösterilir. Ön yüzde de genel updated_at yerine bu içerik tarihleri kullanılır. published_at form girdisi geçmişteki ilk yayın kaydını değiştirmez.
- 200000 migration mevcut yayındaki sayfaların kayıtlı published_at değerini ilk yayın geçmişine taşır; eski updated_at değerlerinden içerik güncelleme tarihi türetilmez. Yayın tarihi bilinmeyen eski kayıt için tarih uydurulmaz. Öncesinde C:/xampp/ehliyetgo-backups altında SQLite yedeği alınmıştır. ManagedPagesTest ilk yayımlama, gerçek düzenleme, değişikliksiz kayıt, tarih/durum değişikliği, okuma sayacı ve eski tarihlerin taşınmasını sınar.

### Eski soruların düzenlenmesi

- Eski aktarımda difficulty=orta kaydı, QuestionForm içindeki Kolay/Orta/Zor doğrulamasına uymadığı için Kaydet başarısız oluyordu. 210000 migration eski küçük/büyük harf ve çevre boşluklarını bu üç geçerli etikete dönüştürür; içerik, ilk yayın, güncelleme tarihleri ve son düzenleyen değişmez. Bu tarihsel düzeltmenin ardından difficulty sütunu 250000 migration ile kaldırılmıştır; güncel aktarım kodları bu alanı yazmaz. Öncesinde SQLite yedeği alınmıştır.
- Soru formunda yayın durumu doğrulama hataları görünür ve Türkçedir. Yayın seçenekleri yalnız Yayında/Taslak olarak gösterilir. QuestionEditingTest sadeleştirilmiş şemayı, metin kaydetme/yeniden açma akışını, ilk yayın tarihinin korunmasını, gerçek düzenlemenin Son Güncelleme oluşturmasını ve değişikliksiz/durum kaydının tarihi değiştirmemesini doğrular.
- Tarayıcıda geçici taslak üzerinde editörde yazma, Kaydet ve sayfayı yeniden açma doğrulandı; geçici kayıt ve ona ait test denetim kaydı temizlendi. 591 numaralı gerçek sorunun metni ve tarihleri korunmuştur.

### Altı içerik bölümünün tarih ve düzenleyen kontrolü

- Sorular, Deneme Sınavları, Konu Testleri, Blog Yazıları, Ders Konuları ve Sayfalar ortak TracksPublication, content-dates ve record-date bileşenlerini kullanır. ContentHistoryPanelTest altı bölümde Livewire formundan gerçek düzenleme, aynı sayfada tarih/düzenleyen gösterimi, yeniden açma, liste tarihi, ilk ekleyen/ilk yayın korunması ve başka kullanıcıyla değişikliksiz Kaydet akışlarını doğrular. Soru sırası değişiklikleri ContentReviewFixesTest ile ayrıca sınanır.
- Yayın tarihini elle değiştirmek düzenlemedir. BlogPostForm ve PageForm dakika hassasiyetindeki tarih girdisi değişmemişse veritabanındaki saniyeleri aynen korur. Böylece sıradan Kaydet son düzenleyen veya güncelleme tarihini değiştirmez.
- 220000 migration yalnız denetim geçmişinde açıkça Yayın tarihi düzenlemesi bulunan ve kaydın son değişiklik zamanı ile eşleşen Sayfa/Blog geçmişini tamamlar. Mevcut düzenleyen/güncelleme üzerine yazmaz; bilinmeyen işlem sahibini tahmin etmez. Yerelde SSS ve ilk blog yazısındaki Erol işlemleri gerçek denetim tarihleriyle tamamlandı; yedek C:/xampp/ehliyetgo-backups altında alındı.


### Blog yazarı

- Kullanıcı tercihiyle blogda ilk ekleyen kişi Yazar olarak gösterilir; yazar yazıyı ilk ekleyen panel hesabıdır. BlogPost saving olayı yeni personel kaydında author_name değerini oturum kullanıcısından, düzenlemede değişmeyen creator_name kaydından alır. Sahipliği bilinmeyen eski aktarımların kayıtlı adı korunur. Bu olay tarih takibinden önce çalışır; sahte editör girdisi güncelleme tarihi oluşturmaz.
- BlogPostForm yazar adını salt okunur gösterir ve Livewire alanını Locked ile korur. Başka kullanıcı düzenlediğinde ilk Yazar korunur, Son Düzenleyen ayrı kaydedilir. İlk yayın ve son güncelleme kuralları değişmez.
- 230000 migration bilinen creator_name değerlerini author_name ile eşler; mevcut üç blogda isim zaten Mehmet AKTAŞ olduğu için görünür kişi değişmemiştir. Tarihler korunur. Ön yüz başlıkları da Yazar olarak gösterilir; mevcut /yazar/{slug} bağlantıları çalışmaya devam eder. EditorAndAuthorTest otomatik atamayı, ayrı son düzenleyeni, model/Livewire sahte alan reddini ve ön yüzdeki adı doğrular.

### Tüm içeriklerde elle yayın tarihi

- Altı bölümün tamamında Yayın Tarihi elle düzenlenir. 240000 migration questions/exams/topic_tests/lectures tablolarına published_at ekler ve mevcut first_published_at değerlerini bu alana kopyalar; ilk yayın ve son güncelleme geçmişini değiştirmez. Blog ve Sayfalar mevcut published_at alanlarını kullanır. Öncesinde SQLite yedeği alınmıştır.
- Dört yeni form EditsPublicationDate trait'i ile tarihi yükler ve değişmemiş dakika girdisinde mevcut saniyeleri korur; nullable/date doğrulaması sunucuda uygulanır. Tarih değişikliği son düzenleyeni ve yayımlanmış içerikte Son Güncelleme tarihini kaydeder. İlk Yayınlanma tarihsel kayıt olarak sabit kalır.
- Ortak content-dates bileşeninde Son Düzenleyen yalnız kayıtlı isim doluysa görünür; isim yoksa başlık ve yer tutucu gösterilmez. Yeni kayıt oluşturan kişi Ekleyen/Editör alanında tutulur; ilk oluşturma bir düzenleme sayılmaz.
- Yeni tarih alanları otomatik zamanlama eklemez; Soru/Deneme/Konu Testi/Ders ve Sayfa görünürlüğü Yayın Durumu ile belirlenir. Blogun mevcut ileri tarihli yayın akışı korunur. ContentHistoryPanelTest altı formda tarihi değiştirme, yeniden açma, tekrarlı kaydetme, geçersiz tarih reddi ve düzenleyen bilgisini doğrular.

### Panel incelemesi sonrası 2, 5 ve 11

- Sayfalar listesinde başlık/slug araması ortak bir where grubu içinde uygulanır; durum filtresi her iki arama eşleşmesi için de geçerlidir. ManagedPagesTest taslak/yayında/tüm durumlarda başlık ve slug eşleşmelerini sınar.
- Kullanıcı tercihiyle hiç yayımlanmamış taslağın ilk yayın tarihi boş kalır; taslak düzenlemesi Son Güncelleme üretmez. İlk kez gerçekten yayımlandığında first_published_at işlem zamanını alır; daha önce girilmiş yayın tarihi bu geçmiş kaydını geriye çekmez. Yayın sonrasındaki gerçek değişiklik Son Güncelleme ve Son Düzenleyen olarak kaydedilir. Bu davranış altı formda ContentHistoryPanelTest ile doğrulanır; blogun ileri tarihli planlama testleri ayrıca korunur.
- Üstte Listeye Dön oku bulunan Blog, Deneme, Sayfa, Soru, Ders Konusu, Konu Testi ve Trafik İşareti formlarındaki yinelenen Vazgeç bağlantısı kaldırıldı. Geri oku ve tek Kaydet düğmesi korunur. Diğer inceleme maddeleri kullanıcı isteğiyle ertelenmiştir.


### Alt bilgi ve alt bağlantı sekmeleri

- Menü Yönetimi üst sekmeleri: Masaüstü Menüsü, Mobil Menüsü, Alt Bilgi Menüsü, Uygulamalar ve Alt Bağlantılar. NavigationItem::ADMIN_MENUS sekmelerin ve rotaların ortak kaynağıdır.
- Alt Bilgi Menüsü apps dışındaki footer_group dolu sütun bağlantılarını ve dinamik sütun yönetimini gösterir. Uygulamalar sekmesi yalnız footer_group=apps kayıtlarını ve uygulama sütunu ayarlarını gösterir. Alt Bağlantılar yalnız footer_group=null kayıtlarını gösterir; sütun seçimi ve başlık formu bulunmaz. Veritabanında iki bölüm de menu=footer olarak saklanır, mevcut ön yüz ve bağlantılar korunur.
- forAdminMenu kapsamı liste, arama, sıra önerisi, düzenleme ve silmede uygulanır. Yanlış sekmeden başka kayıt düzenlenemez/silinemez. Sütun formunda grup zorunlu, Alt Bağlantılar formunda grup yasaktır. Eski genel kimlikle düzenleme rotası doğru sekmeyi otomatik belirler. Kaydet düzenleme formunda kalır; yeni kayıt kendi sekmesine döner. Yeni izin anahtarı veya veri migration gerekmez.

- Ekleyen/Yazar form alanları Yayın Durumu ile uyumlu üst etiket ve yuvarlatılmış çerçeveli kutu olarak gösterilir. Blog Yazar alanı ve ortak creator-info bileşeni salt okunur kalır; kişi bilgisi otomatik kayıt mantığıyla belirlenir.


### Zengin editörde video butonu

- Ortak rich-editor araç çubuğunun sonundaki video simgesi (ipucu: Video Ekle), aynı editörde bağlantı/embed girişini açar; modal kullanılmaz. Video son imleç konumuna eklenir, aynı sayfadaki diğer editör etkilenmez. Soru altındaki eski bağımsız embed alanı kaldırılmıştır.
- QuestionForm, BlogPostForm, PageForm ve TopicForm ortak ResolvesEditorVideo metodunu kullanır. Metot QuestionMedia doğrulamasından geçmiş HTML döndürür; içerik kaydetmez. YouTube/Vimeo bağlantıları, dış iframe kodu ve doğrudan video dosyaları desteklenir. Geçersiz giriş aynı alanda gösterilir. Kaydet ile normal içerik akışı devam eder.
- Tarayıcıda buton açılışı, geçersiz adres reddi, imleç konumuna video ekleme ve iki editörün bağımsızlığı doğrulandı; gerçek soru kaydedilmeden kontrol sekmesi kapatıldı. ContentReviewFixesTest dört formda çözümlemeyi ve soru videosunun kayıt/yeniden açma/öğrenci gösterimini sınar.


### Zengin editörde görsel yükleme

- Araç çubuğunun sonunda Görsel Yükle simgesi, ardından Video Ekle simgesi bulunur. Görsel butonu dosya seçiciyi açar; JPG/PNG/WEBP, en fazla 2 MB ve 4096×4096 piksel kabul edilir. Sunucu dosya içeriğini doğrular; SVG ve çalıştırılabilir dosyalar kabul edilmez.
- QuestionForm, BlogPostForm, PageForm ve TopicForm UploadsEditorImages traitini kullanır. Dosyalar kullanıcının istediği question-image klasörüne, public disk üzerinden storage/app/public/question-image altında rastgele dosya adıyla kaydedilir. public/storage bağlantısından sunulur. İçerikte dosya değil URL tutulur.
- Her editörün yüklemesi ayrı alan anahtarıyla takip edilir; soru ve açıklama birbirini etkilemez. Görsel son imleç konumuna yerleşir, yükleme ilerlemesi/hataları editörde görünür. Dosya yüklenince diske yazılır; içerikle ilişkisinin kalıcı olması için form Kaydet gerekir. Editörden görseli çıkarmak disk dosyasını otomatik silmez (başka içerikler aynı URLyi kullanabilir).
- EditorImageUploadTest dört formda klasörü, benzersiz adları, uygunsuz/çok büyük dosyaları, bağımsız editör yüklemelerini ve soru kaydı/yeniden açılışını doğrular. Yerel tarayıcı denemesinde görsel 240×100 piksel olarak public/storage üzerinden açıldı; gerçek soruyu kaydetmeden kontrol sekmesi kapatıldı ve yalnız test görseli diskten temizlendi.

- Soru formundaki eski Medya Türü, Görsel / Video URL veya Dosya Yolu ve bağımsız medya önizlemesi kaldırılmıştır. Yeni görsel/video ekleme zengin editörün araç çubuğundan yapılır. Eski image_url verileri question_text içine taşınmıştır; image_url/media_type sütunları ve ayrı question-media bileşeni kaldırılmıştır.

- Soru formunda Kolay/Orta/Zor (Zorluk Seviyesi) kaldırıldı. QuestionForm zorluk alanını yüklemez, doğrulamaz ve kaydetmez; difficulty sütunu da 250000 migration ile kaldırılmıştır. Yeni aktarımlar zorluk alanı yazmamalıdır.


## 29 Ekim 2026 — Veritabanı sadeleştirmesi (güncel yapı)

- 250000_consolidate_content_schema migration'ı kullanılmayan 27 sütunu ve 3 eski tabloyu (question_topics, topics, content_categories) kaldırır. Eski migration dosyaları kurulum geçmişi olarak korunur; bunları silmeyin veya geçmişi yeniden yazmayın.
- Soru şıkları yalnız questions.options JSON alanındadır. option_a–option_e kaldırıldı; eski dolu JSON öncelikli korunur, boş JSON eski şıklardan tamamlanır. Doğru cevap ve bütün soru kimlikleri korunur. Yeni seeder ve formlar yalnız JSON yazar.
- Sınav/konu testi ilişkileri yalnız exam_question / question_topic_test, sıra yalnız position alanındadır. Eksik eski ilişkiler taşınır; mevcut pivot sırası, oturum soru listeleri ve geçmiş cevaplar değiştirilmez.
- Dersler yalnız lectures / lecture_categories kullanır. Birebir aynı eski dersler birleştirilir; yalnız eski tabloda bulunan veya farklı içerikle aynı slug'ı kullanan dersler taslak olarak korunur. Kimliği bilinmeyen ekleyen/düzenleyen bilgisi uydurulmaz. Hız Kuralları ve Takip Mesafesi eski tablodan taslak taşındı.
- Eski ders görüntülenme sayıları ve SEO verileri lectures.view_count / seo_title / seo_description alanlarına taşınır. Ders okuma sayacı yayın/güncelleme tarihlerini ve son düzenleyeni değiştirmez; sayfaya özel SEO başlık/açıklaması kullanılır.
- Rol kimliği key, izinler permissions JSON alanındadır; permissions_count / is_super_admin / is_system kaldırıldı. Menü ve yetki kaydı AdminAccess üzerinden çalışmaya devam eder.
- exams.mode/is_public/is_shuffle, topic_tests.duration_minutes, integrations.settings, blog_posts.image_url, advertisements.target_url/image ve eski menü bayrakları kaldırıldı. Entegrasyon gizli bilgileri mevcut şifreli configuration alanındadır. SEO ayarları korunur.
- Çalışmayan eski Filament panel kaynakları, eski Topic/ContentCategory/QuestionTopic modelleri ve question-media bileşeni proje dışındaki retired-code-20261029 arşivine taşındı. Kullanılan panel tam sayfa Livewire panelidir.
- SQLite aktarımı yabancı anahtar silme etkilerini kapatarak transaction içinde çalışır; commit öncesi foreign_key_check doğrulanır. Bilinmeyen dolu integration.settings veya geçersiz şık/medya aktarımı durdurur ve geri alır. SchemaConsolidationTest veri aktarımını, çakışmaları, ilişkileri ve hata geri alımını sınar.
- Geri dönüş için migration down kullanılmaz: veri birleştirmesi geri üretilemez. İşlem öncesi tutarlı SQLite ve eşleşen kod yedekleri C:/xampp/ehliyetgo-backups/ altındadır (before-schema-cleanup-20261029.sqlite ve before-schema-cleanup-code-20261029.zip). Restore sırasında kod ve veritabanı birlikte geri alınmalıdır. Rehearsal ve ana veritabanı karşılaştırma raporları da bu klasördedir.


### 30 Ekim 2026 — Test oturumu üst barı

- Deneme ve konu testi çözme sayfaları test-session Blade bölümünü tanımlar; bu sayfalarda genel masaüstü/mobil ana menü ve sayfa yolu gösterilmez. Ortak test-session-bar bileşeni en üste sabitlenir: Çıkış, test adı, denemelerde kalan süre / konu testlerinde Süresiz. Çıkış düğmesi kırmızı tonlardadır; mobil dahil bütün ekranlarda başlığın solunda, aynı satırda kalır. Bitirme düğmesi üst barda bulunmaz; son soruda Sonraki Soru düğmesi Testi Bitir / Sınavı Bitir olur ve mevcut bitirme onayını açar. Önceki soruya dönünce tekrar Sonraki Soru görünür. Sonuç ve liste sayfaları normal menüyü kullanır.
- Çıkış oturumu sonlandırmaz, ilgili test listesine döner. Ortak leaveTest işlemi bekleyen cevap kayıtlarını bekler; kayıt hatasında ekranda kalır ve tekrar kaydetme ister. Süre dolmasına bağlı sonuçlandırma çıkıştan önceliklidir. Alt soru haritasındaki tekrar bitirme düğmeleri kaldırılmıştır.
- exam-answer-actions.test.cjs çıkış/kayıt yarışlarını, başarısız kayıtları ve zaman aşımı sonuçlandırmasını da doğrular.

- Test başlığının altında cevaplanan soru sayısının toplam soruya oranıyla dolan ilerleme çubuğu bulunur; soru haritasından başka soruya geçmek tek başına ilerlemeyi artırmaz. Konu Testi / Deneme Sınavı etiketi sağda süreyle birlikte rozet olarak gösterilir; mobilde bu rozetler sağda alt alta yer alır.


### 30 Ekim 2026 — Test çözme adresleri

- Çözme ekranları denemelerde /deneme/{exam:slug}, konu testlerinde /konu-testleri/{category:slug}/{test:slug} adreslerindedir (exams.play / topic-tests.play). Paneldeki mevcut slug değerleri kullanılır; başlık veya slug kendiliğinden yeniden yazılmaz. Konu testinin kategori eşleşmesi controller içinde doğrulanır.
- ExamAttempt::playUrl() devam et ve başlatma yönlendirmelerinin ortak adres kaynağıdır. Eski exams.take / topic-tests.take adresleri erişim iznini kontrol ettikten sonra yeni adrese yönlendirir; tamamlanan oturumlar kendi sonuç sayfasına gider. Cevap, bitirme ve sonuç uçları oturum kimliğini korur.
- Aynı temiz adres her ziyaretçinin kendi en son oturumunu açar. Üyelik kimliği veya misafir oturum çerezi eşleşmeden başka bir kişinin cevapları yüklenmez. Yenileme yeni oturum üretmez, süreyi ve cevapları sıfırlamaz. Aktif test HTML yanıtları private/no-store ve noindex başlıkları taşır.
- Henüz kendi oturumu olmayan kişi paylaşılan adresi açınca test başlatma ekranını görür; GET ile yeni kayıt oluşturulmaz. Başlatma yine CSRF korumalı POST işlemidir. Taslak ve üyelik kısıtları bu ekranda da korunur.
- Exam / TopicTest slug değişimleri otomatik SEO yönlendirme sistemine bağlıdır. Konu kategorisi slug değişikliği ve testin kategori değişimi de çocuk test adreslerini taşır. Yönetici yönlendirmeyi silerse eski adres tekrar oluşturulmaz ve 404 olur.
- TestPlayUrlTest yeni adresleri, kişiye özel oturum seçimini, misafir/üye ayrımını, yenilemeyi, yeniden başlatmayı, kategori eşleşmesini ve slug yönlendirmelerini doğrular.

- Paneldeki Canlı Bağlantı kutuları kayıtlı veriden üretilir. Deneme ve konu testi ilgili play rotasına, ders ilgili kategori/ders adresine gider. Blog ve sayfalarda kaydedilmemiş slug değişikliği canlı adresi değiştirmez; Kaydet sonrası yenilenir. Taslak içerikte ve pasif soru kategorisindeki testte canlı bağlantı gösterilmez. AdminLiveLinkTest hedef adresi, kaydetme öncesi/sonrası slug ve yayın görünürlüğünü doğrular.

### 30 Ekim 2026 — Silinen yönlendirme ve tarayıcı önbelleği

- SEO 301/302/410 yanıtları private, no-store, no-cache, max-age=0, must-revalidate ile döner; silinen veya pasifleştirilen kurallar tarayıcıda kalıcı olarak saklanmamalıdır. HTTP durum kodları değişmez.
- Yönlendirmeler listesinin tam sayfa yanıtı Clear-Site-Data: "cache" gönderir; önceki sürümden kalan tarayıcı HTTP önbelleğini temizler, çerez ve localStorage verilerini silmez. Bu başlık HTTPS ve güvenilir localhost ortamında destekleyen tarayıcılarda çalışır; başka ziyaretçilerin daha önce saklanmış yanıtları sunucudan geriye dönük zorla değiştirilemez. Normal site sayfaları bu başlığı göndermez.
- SeoRedirectTest GET/HEAD önbellek başlıklarını, Livewire üzerinden silme sonrası 404 durumunu, yalnız yönetim sayfasındaki cache temizliğini ve girişin korunmasını doğrular. Bildirilen eski konu testi adresi aynı tarayıcıda düzeltme öncesi yönlenirken düzeltme sonrası 404 ile doğrulandı.

### Siteye uygun 404 sayfası

- errors/404.blade.php site renkleri, Plus Jakarta Sans yazı tipi ve yol temalı SVG ile mobil/masaüstü hata sayfasını sunar. Logo, favicon, site adı ve telif bilgisi SiteSetting kaydından gelir. Ana sayfa, konu testleri, deneme sınavları, dersler, blog ve iletişim bağlantıları named route kullanır.
- Laravel gerçek HTTP 404 durumunu korur; meta robots noindex, follow kullanılır. Kullanıcı tercihiyle layouts.app ortak şablonu kullanılır; normal üst menü ve alt bilgi aynen korunur. İçerikte arama formu ve Ana Sayfaya Dön bağlantısı bulunur.

- /arama (search) GET sayfası q parametresiyle yayındaki deneme, aktif kategorideki konu testi, ders, blog ve sayfa başlıklarında arar. İleri tarihli bloglar ve taslaklar gösterilmez. Sonuçlar 12 kayıtlık sayfalama ile gerçek named route adreslerine gider; aynı başlıklı testler kategori adıyla ayrılır. Boş/geçersiz arama tüm veriyi listelemez; uzunluk 100 karakterle sınırlıdır ve LIKE özel karakterleri kaçırılır. SiteSearchTest görünürlük, doğru hedefler, sayfalama, güvenli girdi ve 404 ortak menü/footer/form davranışını doğrular.

- 404 sayfası hide-ads bölümünü tanımlar; ortak layout bu durumda reklam sorgusu, header/yan banner kodu ve reklam yerleşim boşluğu oluşturmaz. Ortak site-ad bileşenindeki görünür Reklam etiketi kullanıcı isteğiyle kaldırılmıştır; iframe başlığı ve sandbox korunur. AdvertisementDevicesTest aktif dört reklam alanı varken hem eksik rota hem eksik model 404 yanıtlarında reklamların bulunmadığını doğrular.
- 30 Ekim 2026 kullanıcı isteğiyle mevcut desktop.Header ve mobile.Header kayıtlarının aa içeriği örnek EhliyetGo HTML bannerıyla değiştirildi; iki kayıt da aktif ve herkes kitlesinde, panelden yönetilebilir. Keşfet bağlantısı /deneme adresini yeni sekmede açar. Öncesinde C:/xampp/ehliyetgo-backups/before-example-banner-20261030-182539.sqlite yedeği alındı.

- Üst reklam alanında masaüstü ve mobil için üst/alt boşluk padding-block:16px ile eşittir; dış dikey margin kullanılmaz ve flex-shrink:0 ile alan sıkışmaz. Tarayıcıda iki cihazda da iframe ile kapsayıcı arasında üstte ve altta 16px ölçüldü.

### 30 Ekim 2026 — Dinamik alt bilgi sütunları ve Uygulamalar

- 190000 migration navigation_groups.is_active ekler; mevcut başlık, sıra ve bağlantılar korunur. Normal sütunlarda sabit key listesi yoktur; yeni key sunucuda UUID ile üretilir, düzenlemede değişmez. NavigationGroupForm tam sayfa oluşturma/düzenleme sunar; düzenleme Kaydet sonrası aynı sayfada kalır.
- NavigationItems footer/apps sekmelerinde sütun gizle/göster, yukarı/aşağı taşı ve silme vardır. Silme önce aynı sayfada sütun adını ve bağlantı sayısını gösterir; onaylanan işlem transaction içinde yalnız o sütunun footer bağlantılarını ve sütunu siler. Gizleme bağlantıları korur. Sıralama Uygulamalar dahil bütün sütunlar arasında uygulanır.
- /admin/navigation-items/apps sekmesi mevcut App Store/Play Store kayıtlarını yönetir; yeni uygulama bağlantısı, sıralama, kitle, aktiflik ve isteğe bağlı URL desteklenir. Boş URL mağaza düğmesini bağlantısız gösterir. Sütun silinirse uygulama sekmesinden yeniden oluşturulabilir; sayfa açılışı silinen sütunu kendiliğinden oluşturmaz.
- Grup ve bağlantı işlemleri seçili sekmeye göre sunucuda filtrelenir; footer normal sütunlarını apps sekmesinden değiştirmek mümkün değildir. Silinen sütuna eski açık formdan bağlantı kaydı reddedilir. Bu alt sayfalar mevcut navigation-items iznine bağlıdır; yeni izin veya editöre otomatik erişim eklenmez.
- Ön yüz yalnız aktif ve ziyaretçiye görünür bağlantısı olan sütunları gösterir; auto-fit grid boş sütun yeri bırakmaz. Mağaza rozetleri korunur, alt telif bağlantıları bağımsızdır.
- FooterColumnsTest tam sayfa GET açılışları, yeni sütun CRUD, gizleme/koruma, sıralama, onaylı silme, uygulama ayrımı/yeniden oluşturma, eski form reddi ve izinleri doğrular. FooterNavigationTest ve NavigationMenusTest yeni sekme ayrımına uyarlandı. Veritabanı yedeği: C:/xampp/ehliyetgo-backups/before-footer-columns-20261030-183420.sqlite.

### 30 Ekim 2026 — Çoklu reklam kitlesi ve Premium ayrımı

- advertisements.audience aynı sütunda JSON dizi tutar; model array cast kullanır. Herkes (everyone), Üyeler (members: Premium olmayan giriş yapmış kullanıcılar), Ziyaretçiler (guests) ve Premium Üyeler (premium) işaret kutularıdır. Birden fazla özel grup seçilebilir; Herkes tek başına tüm grupları kapsar. Hiç seçim yapılmadan kayıt reddedilir.
- Premium denetimi User::isPremium() üzerinden premium_until gelecekte mi kontrolüdür. Süresi bitmiş Premium hesap normal üyedir. Her istek sunucuda ziyaretçinin grubunu seçer; uygun olmayan reklam kodu HTML’ye eklenmez. Kitleye özel reklamlar Herkes yedeğinden önce, aynı öncelikte updated_at/id sırasıyla seçilir. Liste seçilmiş grupları beraber gösterir; filtre JSON üyeliğini kullanır.
- 200000 migration eski members değerini [members,premium] olarak taşır; eski tüm üyelere gösterim korunur. everyone/guests diziye dönüşür; içerik, tarih ve aktiflik değişmez. Yeni varsayılan JSON [everyone] olur. Çoklu seçim tek eski değere kayıpsız dönüştürülemediği için geri dönüş yedekle yapılır. Yedek C:/xampp/ehliyetgo-backups/before-ad-audiences-20261030-184100.sqlite.
- AdvertisementAudienceTest çoklu seçme/kaydetme/yeniden açma/filtre, boş ve hatalı grup reddi, cihaz ve konumlar, Premium süresinin dolması ve eski kapsamın korunmasını doğrular. Tarayıcıda çoklu seçim ve Herkes seçince diğer kutuların temizlenmesi doğrulandı; seçim değişiminde wire:key yenilenerek checkbox işareti sunucu değeriyle aynı tutulur.

### 2 Kasım 2026 — SEO doğrulama meta etiketleri

- SEO Ayarları içinde ve sol menüde Doğrulama Ayarları bulunur; seo-verification iznini kullanır. Google, Bing ve Yandex alanları boş başlar; kimlik veya servisle eşleşen tam meta etiketi kabul eder. Diğer Servisler alanı alt alta en fazla 20 name/content meta etiketi kabul eder. Baidu, Pinterest ve Norton için ayrı alan yoktur; varsa kayıtlı etiketler Diğer Servisler alanında korunur.
- SiteVerification doğrulaması yanlış servis, tekrar eden etiket, script, ek HTML/öznitelik, http-equiv ve mevcut SEO etiketlerini değiştiren girdileri reddeder. Ham HTML saklanmaz; seo_settings.verification_tags JSON alanında ad/kimlik çiftleri saklanır. layouts.app bunları head içinde Blade kaçışlamasıyla üretir; yönetici layout'una eklenmez. Boşaltıp kaydetmek etiketi siteden kaldırır.
- Kaydet aynı formda kalır; doğrulama servislerine istek atmaz. Kullanıcı gerçek kodunu kaydettikten sonra ilgili sağlayıcının doğrulama işlemini tamamlar. Ekran görüntüsündeki örnek kimlikler başlangıç verisine eklenmez.
- SeoVerificationTest kimlik/tam etiket kaydı, yeniden açma, public head çıktısı, temizleme, geçersiz girdi reddi ve HTML kaçışlamasını sınar. Migration öncesi yedek: C:/xampp/ehliyetgo-backups/before-seo-verification-20261102-114808.sqlite.
- SEO ayarları, yönlendirme listesi ve 404 listesi ortak admin.seo-tabs bileşenini kullanır: Genel Ayarlar / Sayfa SEO Ayarları / Yönlendirmeler / 404 Kayıtları / Doğrulama Ayarları. Doğrulama Ayarları sol menüdeki SEO Yönetimi grubunda da son sıradadır. Sekmeler başlığın altında yer alır; etkin sekme aria-current ve mavi görünümle belirtilir. Editör yalnız erişim izni olan sekmeleri görür. Doğrulama alanları /admin/seo-settings/verification adresindeki ayrı SeoVerification formundadır; admin.seo-verification rotası AdminAccess kaydı ve seo-verification iznine bağlıdır; Editör için varsayılan kapalıdır. Genel Ayarlar yalnız genel alanları, doğrulama formu yalnız verification_tags alanını kaydeder; bir sekmede kaydetmek diğerinin güncel verisini ezmez.

### 2 Kasım 2026 — İçerik ve sayfa SEO yönetimi

- Keywords meta etiketi, panel alanı, model/seed kullanımı ve seo_settings.keywords sütunu kaldırıldı. SiteVerification içindeki keywords adı yalnız yasaklı etiket listesinde kalır; ham doğrulama etiketleriyle tekrar eklenemez.
- Exam, TopicTest, BlogPost, Lecture, Page, TopicCategory, LectureCategory, BlogCategory ve TrafficSignCategory ortak HasSeo trait'ini kullanır: seo_title, seo_description, seo_canonical, seo_index (inherit/index/noindex). Mevcut Page/Lecture SEO metinleri korunur. Migration mevcut içeriklerin tarihlerini ve sahipliğini değiştirmez.
- Bu dokuz türün oluşturma/düzenleme formu EditsSeo ve admin.seo-fields bileşenini kullanır. SEO ayarları mevcut tek Kaydet ile içerikle birlikte doğrulanır ve kaydedilir; düzenleme aynı ekranda kalır. Canonical yalnız mutlak HTTP(S) adres kabul eder; kullanıcı bilgisi ve fragment kabul etmez. Noindex erişimi veya yayın durumunu değiştirmez.
- SEO Ayarları → Sayfa SEO Ayarları (/admin/seo-settings/pages, admin.seo-settings.pages) mevcut seo-settings iznine bağlıdır. SeoPages, SeoPageCatalog listesindeki ana sayfa, deneme/konu testi/ders/blog listeleri, iletişim, trafik işareti sayfaları ve yayımlanmış yazarlara ait sayfaları yönetir. Bunlar seo_route_settings tablosunda route_key ile saklanır; içeriklerin SEO alanları kendi formlarındadır. Yeni izin veya otomatik Editör erişimi eklenmedi.
- layouts.app içinde PageSeo tek title, description, canonical ve robots üretir. Özel SEO başlığı aynen kullanılır; boş başlık normal sayfa başlığına, boş açıklama içerik özeti/açıklaması veya genel açıklamaya döner. Canonical boşsa genel canonical_base ve güncel model adresinden üretilir; slug/kategori değişikliğini izler. Blog kategori parametresi ve sayfalama korunur; takip parametreleri canonical'a eklenmez. /ders-notlari canonical'ı /konu; animasyonlu sayfaların canonical'ı gerçek konu kategorisidir.
- Aktif sınav oturumları, sonuçları, hesap/giriş ekranları otomatik noindex kalır; arama ve 404 noindex/follow davranışını korur. Özel oturum ve hata yanıtında canonical üretilmez. Taslak erişim denetimleri değişmez. Bireysel soruların ve trafik işaretlerinin herkese açık bağımsız detay GET adresi olmadığından onlara sahte SEO adresi/alanı eklenmez.
- Yeni herkese açık içerik veya adres eklerken SEO alanı, form ve PageSeo eşlemesini birlikte ekleyin; sabit/liste adresini SeoPageCatalog'a ekleyin. Aynı head içinde ikinci title/description/canonical/robots veya keywords üretmeyin. İçerik tarihleri ve son düzenleyen takibi mevcut TracksPublication davranışını kullanır.
- ContentSeoTest dokuz formda kaydetme/yeniden açma/ön yüz çıktısını, canonical ve slug/kategori değişikliklerini, sayfalamayı, sabit/yazar ayarlarını, geçersiz adresleri, HTML kaçışlamasını, keywords kaldırılmasını ve özel/taslak erişimini sınar. SchemaConsolidationTest geçmiş şemayı artık yalnız birleştirme öncesi migration'larla kurar; sonraki migration'lar kronolojik sırayla uygulanır.
- Yedek: C:/xampp/ehliyetgo-backups/before-content-seo-20261102-120511.sqlite. 150000 migration keywords sütununu kaldırır; eski keyword değerleri yalnız bu yedekten geri alınabilir.
- SEO kutusu içerik formlarında her zaman son içerik bölümü olarak ayrı kartta kalır; deneme/konu testlerinde soru seçimi ve sıralamasından da sonra gelir. Kategori formlarında ana bilgi kartına gömülmez. Index/Noindex aynı grupta tek seçimli radio seçenekleridir. Canonical boş bırakıldığında sayfanın güncel adresi kullanılır; özel adres yalnız kullanıcı girdiğinde uygulanır.


### 2 Kasım 2026 — İçerik türüne göre genel SEO kuralları

- Genel Ayarlar formu seo_settings.content_rules JSON alanında sekiz türün başlık/açıklama/Index kurallarını tutar: deneme, konu testi, blog, ders, sayfa, kategoriler, sabit/liste sayfaları ve yazar sayfaları. SeoTemplates türleri, izinli değişkenleri ve metin üretimini merkezileştirir. Başlangıç şablonları boştur; mevcut otomatik üretim korunur.
- Öncelik her alan için ayrı uygulanır: özel SEO metni > içerik türünün genel şablonu > önceki otomatik başlık/özet. %baslik%, %site_adi%, %ozet%, %kategori%, %yil% desteklenir; bilinmeyen değişken reddedilir. Metin tek geçişte genişletilir, PHP/Blade çalıştırılmaz ve HTML çıktısı kaçışlanır. Önizleme temsili içerikle çalışır.
- İçerikte Genel ayarı kullan (inherit) seçiliyse türün Index/Noindex kuralı her istekte okunur; açıkça seçilmiş index/noindex önceliklidir. Formlar ve HasSeo model başlangıcı yeni kayıtları inherit yapar. Ham DB insert kullanacak yeni kod inherit değerini açıkça vermelidir; SQLite tablolarını/ilişkileri yeniden kurmamak için eski sütun varsayılanı değiştirilmemiştir.
- 160000 migration kullanıcı onayıyla yalnız mevcut index değerlerini inherit yapar; noindex, özel metin, canonical, içerik/tarih/sahiplik alanlarını değiştirmez. Canlı veride 58 kayıt aktarıldı; içerikler ve soru ilişkileri karşılaştırılarak doğrulandı. Yedek: C:/xampp/ehliyetgo-backups/before-seo-template-rules-20261102-145938.sqlite. Geri alma inherit değerlerini index yapar; geçmişteki özel/varsayılan Index ayrımı ancak yedekten geri getirilebilir.
- Canonical genel şablona bağlı değildir; özel adres boşsa güncel sayfa adresidir. Özel oturum/hata/arama ekranlarının zorunlu noindex davranışı korunur. Genel ve doğrulama formları birbirlerinin alanlarını ezmez. Yeni genel içerik türlerinde SeoTemplates eşlemesini, PageSeo ve içerik formunu birlikte güncelleyin.
- SeoTemplateRulesTest tüm türlerin ön yüz eşlemesini, alan bazlı önceliği, dinamik devralmayı, kaydetme/yeniden açmayı, doğrulamayı ve veri migration'ını sınar. Tam test sonucu: 284 test / 4763 assertion. Kayıt: C:/xampp/ehliyetgo-backups/seo-template-tests-20261102.txt.


### 2 Kasım 2026 — Ayrı Sitemap Ayarları ve dinamik XML üretimi

- /admin/sitemap-settings, SitemapSettings tam sayfa formudur. AdminAccess ve SEO sekmelerinde sitemap-settings kaydı bulunur; editöre varsayılan erişim verilmez. Son kullanıcı düzenlemesiyle Sitemap Ayarları, Doğrulama Ayarları sonrasında son sıraya alınmıştır. Tek üst Kaydet düğmesi vardır, kayıt sonrası aynı sayfa ve tek başarı bildirimi korunur.
- Genel Ayarlar artık site_title, sitemap_enabled ve robots_txt düzenlemez. Genel form yalnız açıklama, canonical kökü ve tür kurallarını kaydeder; sitemap formu yalnız kendi alanlarını kaydeder. 170000 migration eski site_title değerini yalnız boş ana sayfa SEO başlığına taşır, mevcut özel ana sayfa başlığını ezmez. Ön yüz layout'u eski site_title yerine site adını son yedek olarak kullanır; eski DB alanı tarihsel veri olarak kalır.
- SiteSitemap::SECTIONS 11 bölümün ortak kaynağıdır: denemeler, konu testleri, blog, dersler, sayfalar, dört kategori türü, sabit/liste sayfaları ve yazarlar. Panel kutuları, URL sayıları, public bölüm rotaları ve üretim buradan gelir. seo_settings.sitemap_sections null ise tüm bilinen türler açık; boş dizi ise hiçbiri dahil değildir. Yeni bölüm eklerken görünürlük sorgusunu, SEO türünü ve sitemap testlerini birlikte güncelleyin.
- /sitemap_index.xml ana indekstir, /sitemap.xml uyumluluk adresi aynı indeksi döndürür. /deneme-sinavlari-sitemap.xml, /konu-testleri-sitemap.xml, /sayfalar-sitemap.xml vb. dosyalar dinamik üretilir. Varsayılan 200 URL/dosya, panelde 1–1000 seçilebilir. Taşan dosyalar -sitemap-2.xml biçiminde bölünür. Kapalı/boş/bilinmeyen/sınır dışı alt dosyalar 404; boş seçimde ana indeks geçerli boş XML'dir. Site haritası tamamen kapalıysa ana ve alt dosyalar 404 verir.
- Sadece gerçekten misafire açık yayındaki içerik dahildir: aktif konu kategorisi, yayındaki test/deneme/ders/sayfa, yayın zamanı gelmiş bloglar. Mevcut uygulamada Seviye 4+ denemeler misafiri kayıt ekranına yönlendirdiğinden sitemap dışında tutulur; erişim politikası değiştirilmemiştir. İleri tarihli bloglar, taslaklar, aktif yönlendirme/410 kaynakları, özel veya genel kuraldan gelen Noindex ve farklı geçerli canonical kullanan adresler dışlanır. Sitemap seçiminden çıkarmak sayfanın Index ayarını değiştirmez. Soruların bağımsız public detay adresi yoktur; soru havuzu eklenmez.
- PageSeo::indexValue ve baseUrl, sitemap ile head çıktısının aynı SEO kurallarını kullanmasını sağlar. XML adresleri kaydedilmiş canonical_base kullanır; yerel panel görüntüleme bağlantıları mevcut uygulama sunucusuna gider. Eski slug'lar yerine güncel model adresleri kullanılır. XMLWriter özel karakterleri güvenle kaçışlar; XML namespace ve içerik türü standarttır. İsteğe bağlı XSL stil denemesi uygulama içi tarayıcıda çökme oluşturduğundan çıkarıldı; standart XML tarayıcıda doğrulandı.
- lastmod üretim saati değildir: içerikte gerçek content_updated_at, yayın/ilk yayın tarihi; kategoride updated_at kullanılır. Belirsiz veya ileri tarih verilmez. İndeks için yapay lastmod üretilmez. Önbellek tutulmaz; kaydetme, yayın zamanı ve slug değişimleri sonraki istekte yansır. Sitemap sorguları yönlendirme/sayfa okunma sayaçlarını artırmaz.
- /robots.txt artık Laravel rotasıdır. public/robots.txt yeniden eklemeyin; statik dosya dinamik ayarları atlar. Mevcut tarama satırları korunur, elle girilen Sitemap satırları temizlenerek yalnız aktifken güncel /sitemap_index.xml adresi eklenir. Sitemap ve robots adresleri SEO yönlendirme kurallarından korunur.
- Migration öncesi yedek: C:/xampp/ehliyetgo-backups/before-sitemap-20261102-150818.sqlite. Eski statik robots yedeği: C:/xampp/ehliyetgo-backups/robots-before-sitemap-20261102-150818.txt. İçerik, soru ilişkileri ve rol verileri hash ile değişmeden kaldığı doğrulandı.
- SitemapTest türler/gerçek public URL'ler, checkbox kaydı, kapatma/boş seçim, Noindex devralması, canonical/yönlendirme filtreleri, sayfalama, XML kaçışlama, tarihler, robots, form ayrımı, yetki ve eski başlık migration'ını sınar. Tam paket: 291 test, 5104 assertion. Log: C:/xampp/ehliyetgo-backups/sitemap-tests-20261102.txt.

- 2 Kasım 2026 menü düzeltmesi: Sitemap Ayarları, SEO Yönetimi sol menüsünde ve ortak üst SEO sekmelerinde en sondadır. admin-nav-icon bileşeninde diğer ikonlarla aynı ölçü/çizgi stilinde site haritası SVG ikonu vardır. Bölüm eklerken AdminAccess ve rota yanında menü ikonunu da tamamlayın.

- 2 Kasım 2026 Genel Ayarlar sadeleştirmesi: Üst Meta Açıklaması ve Canonical URL Base alanları UI, Livewire property ve doğrulamadan kaldırıldı. Genel form yalnız content_rules kaydeder. İçerik/sayfa SEO alanları ve mevcut otomatik canonical/sitemap üretimi korunur; kayıtlı eski meta_description ve canonical_base verileri bu ekran düzenlemesinde silinmez veya değiştirilmez. Form ayrımı testleri artık content_rules alanlarını kullanır.

### 2 Kasım 2026 — Kategori türlerinin SEO kuralları ayrıldı

- Genel SEO kuralları artık topic_categories (Soru / Konu Testi Kategorileri), lecture_categories (Ders Kategorileri), blog_categories ve traffic_sign_categories için ayrı başlık/açıklama/Index varsayılanları içerir. SeoTemplates::CATEGORY_TYPES ortak kategori tür listesidir. Toplam 11 SEO türü vardır; mevcut form ve doğrulama bu kayıttan üretilir.
- 180000 migration eski content_rules.categories değerini eksik dört türe kopyalar, mevcut türe özel değerleri ezmez ve eski ortak anahtarı kaldırır. Ayar tarihleri, kategori kayıtları, özel SEO alanları ve yetkiler değişmez. Migration öncesinde eski ortak anahtar okuma yedeği desteklenir. Ayrı kuralları kayıpsız birleştirmek mümkün olmadığından rollback yedekten yapılır. Yedek: C:/xampp/ehliyetgo-backups/before-category-seo-split-20261102-154849.sqlite.
- Ön yüz meta/robots ve sitemap aynı SeoTemplates eşlemesini kullanır; bir kategori türünün Noindex kuralı diğer türleri etkilemez. Kategoriye özel SEO önceliği ve yüzde kategori değişkeni korunur. Önizlemeler türe uygun örnek ad gösterir. Yeni panel bölümü/izin eklenmedi. CategorySeoRulesTest bağımsız kayıt, yeniden açma, ön yüz, sitemap ve mevcut ayarların taşınmasını doğrular.

- Kategori SEO ayrımı sonrası tam test sonucu: 293 test, 5209 assertion. Log: C:/xampp/ehliyetgo-backups/category-seo-tests-20261102.txt.


### Üye ve yazar profilleri (2 Kasım 2026)

- Normal üyelerin herkese açık adresi `/uye/{profile_slug}`, yazarların adresi `/yazar/{profile_slug}` olur. İki profil arasında geçiş bağlantısı gösterilmez. `/profil` ve `/settings/profile` özel hesap alanları olarak kalır.
- Profilde yalnız ad, fotoğraf, biyografi ve üyelik tarihi görünür. E-posta, telefon, doğum tarihi, şehir, sınav sonuçları ve hesap yetkileri yayımlanmaz. Controller görünüme `AuthorProfiles::data()` ile açıkça seçilmiş alanları geçirir.
- `profile_slug` otomatik ve benzersizdir; ad değişince değişmez ve form girdisi değildir. Yeni üyeler, hesabı olmayan eski yazarların adreslerini devralamaz. Yazar yazıları `created_by` üzerinden eşleşir; isim benzerliği sahiplik sayılmaz. Sahibi silinmiş eski yazılar isim tabanlı eski yazar profillerinde kalır.
- Biyografi ve fotoğraf Hesap Ayarları, Üyeler / Öğrenciler ve Yönetim Kullanıcıları düzenleme formlarında yönetilir. `ProfilePhoto` doğrulanmış JPG/PNG/WebP dosyalarını `storage/app/public/profile-images` içinde saklar (2 MB, en fazla 4096×4096). Ham dosya yolu ve profil slug'ı mass assignment ile kabul edilmez.
- Genel SEO'da `members` (Üye Profilleri) ve `authors` (Yazar Profilleri) bağımsız başlık/açıklama/Index-Noindex kurallarıdır. `%ad%` ve `%kullanici_adi%` değişkenleri kullanılabilir. Üyeye özel SEO alanı veya Sayfa SEO kaydı yoktur; yazarın mevcut `blog.author:{slug}` özel SEO kaydı genel kuralları geçersiz kılabilir. Canonical varsayılanı kendi adresidir.
- Sitemap `uyeler` bölümünü içerir; normal üyeleri listeler, yönetim hesaplarını üye bölümüne koymaz ve genel Noindex kuralına uyar. `yazarlar`, özel SEO listesi ve gerçek yazar sayfaları aynı `AuthorProfiles` kataloğunu kullanır.
- `PublicProfileTest` özel alan gizliliğini, adres çakışmalarını, aynı isimli yazarların yazı ayrımını, taslak/gelecek yazıların görünmemesini, SEO/sitemap kurallarını, fotoğraf ve profil kaydını doğrular.

- Üyeler / Öğrenciler listesi ve araması yalnız `is_admin = false` hesapları kapsar. Yönetici/Editör hesapları Yönetim Kullanıcıları listesindedir. Üye listesinde rol filtresi ve tek tıkla panel yetkisi değiştirme yoktur; üye listesinin silme işlemi yönetim hesaplarına uygulanamaz. `MemberListSeparationTest` bu ayrımı doğrular.


- Panel adları: `topics` bölümü **Konu Anlatımları**, `content-categories` bölümü **Konu Kategorileri** olarak gösterilir. Bu bir adlandırma değişikliğidir; mevcut Lecture/LectureCategory veri ilişkileri, rota adresleri ve izin anahtarları korunur. Menü, izin listesi, liste/form başlıkları, SEO ve sitemap bölüm etiketlerinde aynı adlar kullanılır.


- Ön sitede eski Ders Notları etiketi **Konu Anlatımı**, ana adresi `/konu` olarak kullanılır. Kategori ve içerik adresleri `/konu/{kategori}/{icerik}` yapısındadır. `/ders-notlari` rota alias'ı kaldırılmıştır; 301 yalnız paneldeki SEO yönlendirme kaydıyla çalışır, kayıt silinince eski adres 404 döner. 200000 migration mevcut masaüstü/mobil/alt bilgi menülerinin ilgili bağlantılarını günceller; özel başlık, görünürlük ve sıralamayı korur.


- Panel grupları ortak AdminAccess kaydında sırasıyla Genel, Sınav Yönetimi, Konu Anlatımı Yönetimi, Trafik İşaretleri, Blog Yönetimi, Sayfa Yönetimi, Kullanıcı Yönetimi, Premium Yönetimi, İletişim & Bildirimler, SEO Yönetimi ve Site Yönetimi olarak düzenlenmiştir. İletişim Mesajları, Site Yönetimi grubunda Denetim Geçmişi sonrasında en alttadır. Konu anlatımı kategorileri **Konu Kategorileri** adıyla gösterilir; gerçek soru/test kategorileri Soru Konuları bölümündedir. Bu gruplama mevcut izin anahtarlarını ve atamalarını değiştirmez.


### Testlerin gösterileceği bölüm (2 Kasım 2026)

- TopicTest.section yalnız topic_tests ve animated değerlerini taşır. Kategori soru havuzunu, bölüm ise sitede listeleneceği yeri belirler. Panelde ortak Konu Testleri formu ve bölüm filtresi kullanılır. Mevcut izin anahtarları korunur.
- Trafik İşaretleri yalnız öğrenme alanıdır. Bilgini Test Et kartı, özel test rotaları, traffic_signs seçeneği ve test sitemap bölümü kaldırılmıştır. Levha/kategori yönetimi ve öğrenme kayıtları korunur.
- 220000 migration kullanıcı onayıyla oluşturulmuş üç trafik işareti testini, bu testlerin oturumlarını ve yalnız bunlara ait üretilmiş soruları temizler. Başka sınav/test/oturumda kullanılan sorular silinmez. Mevcut Trafik ve Çevre konu testleri ve animasyon testleri korunur. Eski test adresleri 404 verir.
- TopicTest::publicUrl/listUrl ortak bağlantı kaynağıdır. Konu testleri /konu-testleri/{kategori}/{test}, animasyon testleri /animasyonlu-sorular/{test} adreslerini kullanır. Bölüm/slug değişimi silinebilir yönetilen 301 oluşturur. Soru, oturum, cevaplama ve sonuç altyapısı ortaktır.
- TopicTestSectionsTest bölüm izolasyonunu ve ortak çözüm akışını; TrafficSignTestRemovalTest veri temizliğinin kapsamını ve paylaşılan soruların korunmasını doğrular.
- Trafik işareti testleri kaldırıldıktan sonra tam paket: 311 test, 5427 assertion başarılı. Veritabanı yedeği: C:/xampp/ehliyetgo-backups/before-remove-traffic-tests-20261102.sqlite. Üç test, 30 soru ve iki oturum temizlendi; levhalar, kategoriler ve öğrenme kayıtları yedekle karşılaştırılarak korundu.


- Trafik İşaretleri ana adresi /trafik-isaretleri doğrudan öğrenme kategorilerini ve ilerlemeyi gösterir; ara tanıtım sayfası kaldırılmıştır. traffic-signs.index ortak liste rotasıdır. Eski /ogren/trafik-isaretleri yalnız silinebilir panel 301 kaydıyla ana adrese gider. Levha kategori detaylarının /ogren/trafik-isaretleri/{kategori} adresleri korunur. 230000 migration menü bağlantılarını ve eski liste SEO ayarlarını taşır; ana sayfanın özel SEO değerlerini ezmez.

- Doğrudan öğrenme sayfası sonrası tam test sonucu: 313 test, 5444 assertion başarılı.


- Test/deneme slug düzeni: TestSlugs başlıktan adres üretir; konu testlerinde benzersizlik kategori içinde, animasyon testlerinde bölüm genelinde, denemelerde tüm denemeler içindedir. Yeni formda çakışma -2, -3 ekiyle çözülür; elle seçilen adres ve mevcut kaydın başlık değişimindeki adresi korunur. 240000 migration mevcut başlıkları topluca uygular, tarih/sahiplik/soru ilişkilerini değiştirmez ve eski adreslere yönetilebilir 301 ekler. Sistem içi yanlis-sorular-tekrar-testi kimliği korunur.
- Konu testi GET çözümlemesi kategori ve bölümle sorgulanır. Test başlatma POST adresi kayıt kimliği kullanır; aynı slug farklı kategorilerdeki testleri karıştırmaz. SQLite kısmi unique indeksleri kategori/bölüm adres alanlarını veritabanında korur.

- Başlığa göre test adresleri güncellemesi sonrası tam paket: 317 test, 5495 assertion başarılı. Log: C:/xampp/ehliyetgo-backups/title-slugs-full-20261102.txt.


### 2 Kasım 2026 — SEO ayarlarının sadeleştirilmesi (güncel davranış)

- Canonical, sitemap ve robots Sitemap adreslerinin tek kökü config('app.url') / APP_URL değeridir. Yayına alınırken APP_URL gerçek HTTPS alan adıyla ayarlanmalı ve yapılandırma önbelleği yenilenmelidir. Eski seo_settings.canonical_base artık kullanılmaz; özel içerik canonical adresleri korunur.
- Eski seo_settings.meta_description artık head çıktısının varsayılanı değildir. Özel açıklama > desteklenen içerik türü şablonu > içerik açıklaması/sayfa açıklaması > Site Ayarları site_description sırası kullanılır. 250000 migration eski ana açıklamayı yalnız boş ana sayfa SEO açıklamasına taşır ve gizli eski alanları null yapar.
- SeoTemplates::TYPES genel kuralları sürekli içerik türleri, dört kategori türü ve üye/yazar profilleriyle sınırlıdır. pages/static_pages genel kuralları kaldırılmıştır; Sayfalar kendi formunda, ana sayfa/liste sayfaları Sayfa SEO Ayarları ekranında bağımsız yönetilir. Eski şablonlar migration sırasında boş özel alanlara genişletilerek taşınır; özel başlıklar, açıklamalar ve Index/Noindex seçimleri ezilmez.
- Bağımsız sayfa formlarında yalnız Index/Noindex vardır. Yeni Page varsayılanı index; eski inherit de bu sayfalarda index anlamına gelir ve değişikliksiz Kaydet sahte içerik düzenlemesi üretmez. Yazar ve sürekli içerik formlarında Genel ayarı kullan devam eder.
- Yeni varsayılan başlıklar: denemeler %baslik% Ehliyet Deneme Sınavı | %site_adi%; konu testleri %kategori% %baslik% Testi | %site_adi%. 250000 migration yalnız boş genel başlıkları doldurur. Sonradan şablonu bilerek boş bırakmak korunur; içerik üzerindeki özel SEO başlığı her zaman önceliklidir.
- Genel ayar önizlemesi gerçek kaydın adı/kategorisi/özeti üzerinden yalnız şablonu gösterir. Boş şablonda temsili başlık/açıklama uydurulmaz; otomatik bilginin kullanılacağı yazılır. Özel SEO alanlarının önceliği belirtilir, içerik olmayan türde önizleme yoktur.
- Veri dönüşümü öncesi yedek: C:/xampp/ehliyetgo-backups/before-seo-cleanup-20261102.sqlite. İlişkiler, içerikler, tarihler ve sahiplik korundu. SeoCleanupTest gizli değerlerin etkisizliğini, bağımsız ayarları, gerçek örnekli önizlemeyi, başlık ayrımını, dönüşümü ve değişikliksiz kayıtları doğrular.

- SEO sadeleştirmesi sonrası tam test sonucu: 322 test, 5560 assertion başarılı. Log: C:/xampp/ehliyetgo-backups/seo-cleanup-full-20261102.txt.


### 2 Kasım 2026 — Yazar ve üyeler yalnız ortak profil SEO kurallarını kullanır

- Yazar Profilleri (authors) ve Üye Profilleri (members) Genel SEO Ayarları içinde iki ayrı ortak kuraldır. Başlık, açıklama ve Index/Noindex tüm ilgili profillere uygulanır; kişi başına SEO ayarı yoktur. Profil adresleri ve içerikleri değişmez, canonical her profilin kendi adresidir.
- SeoPageCatalog::choices yalnız sabit sayfaları listeler. SeoPages yazar anahtarlarını doğrulamada reddeder. PageSeo ve SiteSitemap eski blog.author:{slug} özel kayıtlarını okumaz; profil başlığı/açıklaması/görünürlüğü yalnız ortak kuralları kullanır.
- 260000 migration eski blog.author:* ve members.show özel SEO kayıtlarını temizler; kullanıcı/yazar hesaplarına, genel kurallara ve diğer sayfa SEO kayıtlarına dokunmaz. PublicProfileTest birden fazla yazarda ortak ayar, eski özel kayıtların etkisizliği, sitemap uyumu ve temizleme kapsamını doğrular.

- Ortak profil SEO güncellemesi sonrası tam paket: 322 test, 5573 assertion başarılı. Log: C:/xampp/ehliyetgo-backups/shared-profile-seo-full-20261102.txt.

- 2 Kasım 2026 güncel kısa kategori adları: question-topics = Soru Kategorileri (soru ve konu testlerinin kategorileri), content-categories = Konu Kategorileri (konu anlatımları), traffic-sign-categories = İşaret Kategorileri. Menü, ilgili form/liste metinleri, SEO ve sitemap etiketleri bu adlarla tutarlıdır; rota/izin anahtarları ve veri ilişkileri değiştirilmez.

- 2 Kasım 2026 Animasyonlu Sorular tek liste adresi /animasyonlu-sorular olmuştur. /animasyonlu-ehliyet-sorulari rota alias'ı kaldırılmıştır; eski adres yalnız SEO Yönlendirmeleri içindeki silinebilir 301 kaydıyla çalışır, kayıt silinince 404 verir. 270000 migration masaüstü/mobil/diğer yerel menü bağlantılarını günceller ve eski self-canonical değerini temizler; özel dış canonical, içerik ve görünürlük ayarları korunur. AnimatedCanonicalUrlTest yönlendirme/silme, menü değerleri, sitemap ve canonical uyumunu doğrular.

- Animasyon adresi düzeltmesi sonrası tam paket: 324 test, 5594 assertion başarılı. Log: C:/xampp/ehliyetgo-backups/animated-url-full-20261102.txt.

- 2 Kasım 2026 kısa menü adları: users = Üyeler, staff-users = Yönetim Ekibi, audit-logs = İşlem Geçmişi. Ortak AdminAccess menü/izin adları ve sayfa başlıkları birlikte güncellenmiştir; rol, rota ve veri yapısı korunur. Blog Yazıları ve Blog Kategorileri adları korunur.

- 2 Kasım 2026 blog detay tasarımı: pages/blog/show ortak site-container/site-card düzenini kullanır. Masaüstünde yazı ve yazar/ilgili içerik sütunları, mobilde tek sütun vardır. Zengin içerik stilleri .blog-article-content ile sınırlıdır; yayın ve son içerik güncelleme tarihleri gerçek kayıttan gösterilir. Tam paket: 324 test, 5594 assertion başarılı; masaüstü ve 390px mobil görünüm kontrol edildi.

### 2 Kasım 2026 — Site adı tek kaynaktan gelir

- Görünen marka adı SiteSetting.site_name üzerinden SiteBrand ve ortak view composer tarafından siteName olarak sağlanır. Yeni şablonlarda marka adını sabit yazmayın; başlıklar, hata sayfaları ve görünür metinler bu değeri kullanır. EhliyetGo yalnız ayar yokken ortak varsayılandır.
- Özel SEO başlık/açıklamaları ve kurumsal Page başlık/özet/içeriği %site_adi% değişkenini destekler. HTML içeriğe eklenen ad escape edilir. Eski genel başlıkta EhliyetGo arayıp değiştiren PageSeo yaklaşımı kaldırılmıştır. Özel metinler açıkça token kullanılmadıkça değiştirilmez.
- 280000 migration yalnız bilinen ilk kurulum ana sayfa SEO başlığını ve kurumsal sayfa metinlerini token biçimine çevirir. İçerik/yayın/düzenleyen tarihlerini değiştirmez. Seed metinleri de token kullanır.
- SMTP gönderen adı her gönderimde güncel Site Adından alınır; ayrı from_name form alanı yoktur ve eski kayıtlı değer kullanılmaz. SMTP adresi/kimlik bilgileri, alan adları, sosyal kullanıcı adları ve yazar adları marka adıyla otomatik değiştirilmez.
- DynamicSiteNameTest iki marka değişiminin görünür metinlere, başlıklara, profile, hata sayfalarına ve SMTP ayarlarına yansımasını; HTML escaping, özel SEO korunması ve migration kapsamını doğrular. NotificationProcessingTest gerçek mail callback içinde gönderen adını doğrular.

- Dinamik site adı güncellemesi sonrası tam test paketi: 327 test, 5651 assertion başarılı. Log: C:/xampp/ehliyetgo-backups/site-name-full-tests-20261102.txt. Veri yedeği: C:/xampp/ehliyetgo-backups/before-dynamic-site-name-20261102-213230.sqlite.

### 3 Kasım 2026 — Yalnız temiz MySQL kurulumu (önceki kurulum/güncelleme bölümünün yerine geçer)

- Kullanıcının tercihi: WordPress gibi hostingde boş veritabanını ve yetkili kullanıcısını kendisi oluşturur; kurulumda sunucu, port, veritabanı adı, kullanıcı adı ve şifreyi girer. MySQL 8.0+ / MariaDB 10.3+ ve PDO MySQL gerekir. Kurulum, veritabanı oluşturmaya veya sunucu kullanıcılarını yönetmeye çalışmaz; verilen boş veritabanında tabloları kurar.
- public/install.php Laravel'den önce çalışır. Site adı/adresi, veritabanı bilgileri ve yönetici hesabı istenir; kurulum anahtarı yoktur. Üretimde HTTPS ve alan adının public klasörüne bağlanması gerekir. CSRF, dosya kilidi, veritabanı düzeyinde GET_LOCK, boş veritabanı kontrolü ve kurulum bitince .env/installed.json kilidi korunur. Şifreler HTML'e geri yazılmaz; bağlantı hatalarında SQL veya sırlar gösterilmez.
- InstallState doğrulaması DSN enjeksiyonunu, sistem veritabanlarını ve geçersiz portları reddeder; şifreyi trim etmez. .env içine DB_CONNECTION=mysql ve DB_HOST/PORT/DATABASE/USERNAME/PASSWORD güvenli tırnaklama ile yazılır. APP_KEY yalnız yeni kurulumda üretilir. Başarılı kurulum progress dosyasını temizler.
- Installer migration zincirini çalıştırır; DatabaseSeeder çalıştırmaz. Yönetici, temel menü ve site ayarları oluşur; Editör izinleri varsayılan boş olur. Yarım kalan kurulum yalnız kendi progress kaydı ve aynı veritabanıyla devam edebilir; mevcut yöneticinin şifresi değiştirilmez. MySQL DDL işlemleri atomik değildir; şema aşamasında kesilme halinde dolu/yarım tabloyu otomatik silerek yeniden başlamayın, hatayı inceleyin veya ayrı boş veritabanıyla temiz kurun.
- Kullanıcı isteğiyle Sistem Güncelleme tamamen kaldırıldı: menü/rota/Livewire/form/UpdateManager/app:update komutu yoktur. 2026_11_03_010000 migration eski system-update izinlerini temizler. Laravel'in şema migration dosyaları kurulum için gereklidir ve korunur; kullanıcıya güncelleme ekranı sunulmaz.
- 2026_11_02_240000 migration yeni MySQL kurulumlarında filtreli benzersiz indeks yerine NULL döndüren sanal sütunlar ve benzersiz indeksler kullanır. Konu testinde kategori+slug, animasyonda slug ayrımı korunur. TopicTest::replicate hesaplanan sütunları yeni kayda kopyalamaz. SQLite dalı mevcut veritabanları/testler için korunur.
- 2026_10_29_250000 geçmiş şema birleştirme migration'ı yeni MySQL kurulumunda DDL'yi transaction içine almaz; SQLite'ın transaction ve foreign_key_check davranışı korunur. Bu eski dosyalardaki değişiklikler temiz MySQL kurulumunu mümkün kılar, mevcut SQLite'a eski migration yeniden uygulanmaz.
- Yerel .env ve SQLite içerikleri korunur. Hostinge taşınacak son ZIP henüz hazırlanmadı. Temiz paket mevcut .env, database.sqlite, oturumlar, loglar, özel dosyalar, önbellek ve yedekleri içermez; vendor, uygulama dosyaları ve boş yazılabilir çalışma klasörleri içerir. Mevcut içeriklerin taşınması temiz kurulumdan ayrı iştir.
- InstallationTest geçici paket + gerçek PHP HTTP sunucusu + ayrı rastgele MySQL veritabanı ve yalnız o veritabanına yetkili kullanıcıyla kurulum/giriş/kilit/yanlış parola/dolu veritabanı/.env özel karakterleri akışını sınar. INSTALL_TEST_MYSQL_HOST, PORT, USERNAME, PASSWORD izole test sunucusuna verilmelidir; test rastgele ehg_install_test_* şemaları oluşturup temizler. Uygulama MySQL testi DB_CONNECTION=mysql ve yalnız test için oluşturulmuş ayrı DB_DATABASE ile çalıştırılır; canlı veritabanında test çalıştırmayın.
- Planlı bildirimler için cron gereklidir: * * * * * php /proje/yolu/artisan schedule:run. Bu görev uygulama güncellemesi değildir. SMTP panelden ayrıca bağlanır.

### 3 Kasım 2026 — Premium avantajları

- Aktif Premium ölçütü User::isPremium() / premium_until > now() değeridir. Advertisement::slots Premium kullanıcıda boş döner; mobil/masaüstü header ve yan banner reklam kodu HTML'e girmez. Herkes seçimi de bu kuralı aşamaz. Reklam formu yalnız Herkes, Üyeler, Ziyaretçiler sunar; eski Premium hedefli kayıtlar korunur fakat gösterilmez, düzenlemede geçerli hedef seçilmesi gerekir. Bildirim hedef kitleleri değişmez.
- Yanlışlardan tekrar başlatma ve Detaylı Performans yalnız aktif Premium içindir; standart/süresi dolmuş üyeler açıklamayla Premium sayfasına yönlenir. Doğrudan POST da engellenir. Normal test çözme, temel dashboard ve tamamlanmış sonuçlara erişim korunur.
- WrongQuestionPool hem dashboard sayısı hem tekrar testi için tek kaynaktır. Yalnız kullanıcıya ait tamamlanmış oturumlardaki boş olmayan cevaplar dikkate alınır; soru bazında en son cevap updated_at/created_at, eşitlikte id ile seçilir. Son cevap yanlışsa ve soru Yayında ise havuzdadır. Sonradan doğru havuzdan çıkarır, yeniden yanlış geri getirir. Bitmemiş testler/cevap yer tutucuları/boşlar havuzu değiştirmez. Tek oturum en fazla 50 soru alır.
- ExamAttemptPolicy devam eden yanlış tekrar oturumunda aktif Premium arar: süresi dolunca doğrudan oynama/cevap/bitirme yolları da kapanır. Tamamlanmış sonuçlar sahibine açık kalır; diğer kullanıcıların erişimi engellidir.
- PerformanceReport kendi tamamlanmış resmi deneme/konu/animasyon testlerini hesaplar; yanlışlardan tekrarlar hariçtir. Dönem all/30/90 ve tür all/exams/topic_tests filtreleri doğrulanır. Son 20 oturum puan grafiği, son 5 ortalaması, en az 10 oturum varsa önceki 5 ile fark, yanıtlanan sorular üzerinden kategori başarısı ve 15 kayıtlık sonuç geçmişi vardır. Boşlar kategori yüzdesine dahil edilmez; tekrar yanıtlar her oturumda sayılır. Kayıt yokken veri/başarı tahmini uydurulmaz.
- Premium tanıtımı bu üç gerçek avantajı anlatır; yapay zekâ/geçme garantisi iddiası yoktur. Zayıf konulara özel otomatik test ve ödeme entegrasyonu bu kapsamda eklenmemiştir. Gerçek üyelerin Premium süreleri ve çözüm geçmişleri değiştirilmemiştir.
- PremiumBenefitsTest erişim ve süre sınırını, en son cevap davranışını, oturum sahipliğini, rapor filtreleri/puan/kategori/pagination doğruluğunu ve reklam formu uyumunu doğrular. AdvertisementAudienceTest Premium için tüm reklam kodlarının çıkarılmasını ve süre sonunda standart reklamların dönmesini kapsar.

- Premium güncellemesi sonrası tam test paketi: 338 test, 5819 assertion başarılı. Log: C:/xampp/ehliyetgo-backups/premium-full-tests-20261103.txt. Değişiklik öncesi dosya yedekleri premium-before-20261103 klasöründedir. Gerçek Premium sayfası ve standart üye yönlendirmesi tarayıcıdan, rapor tasarımı geçici örnek verilerle kontrol edildi; geçici dosyalar/sunucu temizlendi.

- MySQL kurulum değişikliği sonrası tam test paketi iki ortamda da başarılı: izole MariaDB 10.4.32 üzerinde 341 test / 5819 assertion (339.10 saniye); mevcut SQLite test ortamında 341 test / 5819 assertion (125.42 saniye). Her iki çalıştırmada gerçek MySQL HTTP kurulum testleri etkin, atlanan test yok. Loglar: C:/xampp/ehliyetgo-backups/mysql-full-tests-20261103.txt ve mysql-installer-sqlite-full-20261103.txt.
- MySQL uyumluluk kontrolünde BlogPosts kategori sıralaması olmayan order sütunu yerine name/id kullanacak şekilde düzeltildi. Altı yönetim formunda unsigned sıralama alanına negatif değer girilmesi doğrulamada reddedilir. İçerik, üyeler ve çözüm geçmişi yerel veri yedeğiyle karşılaştırılarak değişmediği doğrulandı. Dosya yedekleri mysql-installer-before-20261103, veri yedeği before-remove-update-20261102-233742.sqlite altındadır.


### 3 Kasım 2026 — Yerel verilerin MySQL'e taşınması

- Kullanıcı onayıyla mevcut SQLite verileri yeni, boş ehliyetgo_local MySQL veritabanına taşındı. XAMPP MariaDB 10.4.32 / 127.0.0.1:3306 kullanılır; ehliyetgo_app hesabı yalnız kendi veritabanına yetkilidir. Parola rastgele oluşturulup .env içine yazıldı. APP_KEY ve kullanıcı parola hashleri değiştirilmedi.
- Son aktarım kısa bakım modunda yapıldı. Tüm 44 tablo ve 1845 kaydın alan içerikleri satır bazında karşılaştırıldı; yalnız sayı kontrolü yapılmadı. SQLite bütünlük/foreign_key_check ve MySQL yabancı anahtar kontrolleri başarılı. Otomatik kimlik sayaçları SQLite geçmiş sıralarını korur; hesaplanan MySQL sütunları veritabanı tarafından üretilir.
- Üyeler, roller, içerikler, tarihler, SEO/menü/entegrasyon ayarları, oturumlar ve çözüm geçmişi taşındı. Site tekrar açıldı; gerçek HTTP yönetici girişi ve ana site/panel ekranları 200 döndü. Mevcut SQLite dosyası proje içinden kaldırılıp yedeğe taşındı; dosya yokken de site açıldı.
- Yedekler: C:/xampp/ehliyetgo-backups/local-mysql-transfer-20261103 içinde .env.before, source.sqlite (tutarlı son anlık kopya), original-inactive.sqlite (eski dosya), mysql-after.sql ve verification.json bulunur. connection.json yerel bağlantı sırrı içerir; hiçbir yedek/ayar dosyası dağıtım paketine eklenmez.
- Bilgisayar yeniden başlatılınca XAMPP MySQL sunucusunun çalışması gerekir; yerel PHP sunucusu ayrıca başlatılır. Bu görev Windows başlangıcına otomatik servis eklemedi. Sistem Güncelleme özelliği yoktur.
- SQLite bağlantı desteği ve phpunit.xml içindeki bellekte çalışan SQLite test ayarı geriye uyum/test için durur; canlı site SQLite kullanmaz. MySQL tam testleri yalnız ayrı ehg_transfer_test_20261103 veritabanında çalıştırılır; ehliyetgo_local üzerinde test veya migrate:fresh ÇALIŞTIRMAYIN.
- Aktarım sonrası izole MySQL tam testi: 341 test / 5819 assertion başarılı, atlanan test yok (338.63 saniye). Gerçek MySQL HTTP kurulum testleri de çalıştı. Log: C:/xampp/ehliyetgo-backups/local-mysql-transfer-20261103/full-tests.txt. Geçici test veritabanı ve aktarım yardımcı dosyaları temizlendi; çalışan yerel MySQL sunucusu açık bırakıldı.

### 3 Kasım 2026 — Kurulum anahtarı kaldırıldı

- Kullanıcı isteğiyle kurulum anahtarı alanı, dosya üretimi ve doğrulaması kaldırıldı. Kurulum site/veritabanı/yönetici bilgileriyle tamamlanır. CSRF, HTTPS, eşzamanlı kurulum kilitleri, boş veritabanı kontrolü ve .env/installed.json/config.php bulunduğunda kurulumun kapanması korunur. assertOpen kontrolü dosya kilidi alındıktan sonra da tekrar çalışır.
- Anahtarsız kurulum sonrası tam test: 341 test / 5825 assertion başarılı; gerçek MySQL HTTP kurulumu, yönetici girişi ve kurulum kilidi doğrulandı. Log: C:/xampp/ehliyetgo-backups/remove-setup-key-20261103/full-tests.txt.

### 3 Kasım 2026 — Temiz kurulumda içerik ve eski URL kuralları oluşturulmaz

- Kullanıcının talebi: Yeni sitede sayfaları ve yönlendirmeleri kendisi oluşturacak. Kurulum sihirbazı migration çalıştırırken yalnız o işlem için installation.fresh=true kullanır; işlem başarılı/başarısız bitince önceki değer geri yüklenir. Bu değer .env içine yazılmaz.
- Eski SSS oluşturma migration'ı bu modda çalışmaz; üç eski URL yönlendirme migration'ı da başlangıç kuralı eklemez. Tablo yapıları ve gerekli diğer dönüşümler korunur. Yeni kurulum sonunda pages ve seo_redirects tabloları boştur. Olmayan sayfalara ait hazır footer bağlantıları mevcut kurulum filtresiyle pasif kalır.
- Mevcut sitenin sayfa/yönlendirmelerini silen migration veya temizlik sorgusu eklenmedi; çalışan veritabanına dokunulmadı. Normal geçmiş veri dönüşümleri mevcut davranışı korur. İleride geçmiş siteye özel içerik veya URL ekleyen migration yazılırsa temiz kurulumda atlanmalıdır; InstallationTest boş sayfalar/yönlendirmeler ve görünmeyen SSS menüsünü gerçek MySQL kurulumu üzerinden doğrular.
- Temiz kurulum değişikliği sonrası 341 test / 5828 assertion başarılı. Gerçek MySQL HTTP kurulumunda pages ve seo_redirects tablolarının boş olduğu doğrulandı. Log: C:/xampp/ehliyetgo-backups/clean-install-20261103/full-tests.txt.

### 3 Kasım 2026 — Site adresi panelden yönetilir

- Site Ayarları'nda Site Adı altında düzenlenebilir Site Adresi bulunur. Başlangıç değeri kurulumun APP_URL değeridir; ayrı bir veritabanı sütunu veya ikinci adres kaynağı eklenmedi.
- SiteAddress kök HTTPS adresini doğrular (yerelde HTTP serbest); alt yol, kullanıcı/parola, sorgu, fragment ve kontrol karakterlerini reddeder. Kaydet APP_URL ve SESSION_SECURE_COOKIE değerlerini .env içinde atomik değiştirir, diğer .env bilgilerini korur ve yapılandırma önbelleğini kaldırır. Aynı adresle normal ayar kaydı .env dosyasını yeniden yazmaz. Diğer ayarlar kaydedilemezse adres geri alınır.
- Canonical/sitemap/robots ve Google dönüş adresi mevcut app.url kaynağından yeni adresi alır; public medya URL ayarı da aynı istekte yenilenir. Kayıt sonrası formda kalınır. Alan adı/DNS/hosting bağlantısını panel otomatik taşımaz.
- Site adresi sonrası tam paket: 345 test / 5903 assertion başarılı. Kurulumdan gelen adres, panelden kalıcı değişiklik, yapılandırma önbelleği, SEO/medya/Google URL uyumu ve hata halinde geri alma test edildi. Kurulum test sunucusu APP_URL değerini ana süreçten devralmaz. Log: C:/xampp/ehliyetgo-backups/site-address-20261103/full-tests.txt.

### 3 Kasım 2026 — Test başlangıç ara sayfası kaldırıldı

- Konu testleri, animasyonlu sorular ve denemelerde yeni test başlığı, kartın mevcut CSRF korumalı POST başlatma formunu HTML form özelliğiyle gönderir. Başlık ve alt düğme tek başlatma işlemini kullanır. Devam eden konu testinin başlığı mevcut oturuma, biten testinki sonuca gider. Üyelik isteyen denemeler kayıt bağlantısını korur.
- Kullanıcı açıkça doğrudan test URL'si açıldığında da testin başlamasını seçti. pages/exams/ready kaldırıldı. Kullanıcıya/ziyaretçiye ait hiç oturum yoksa GET aynı start metoduyla oturum açıp çözüm adresine döner; takip eden GET mevcut oturumu açar, süre/cevapları sıfırlamaz. Tamamlanan oturum sonuç ekranına gider. GET artık yeni oturum oluşturabildiği için bu davranışı eski no-attempt-on-GET varsayımına geri çevirmeyin.
- Yayın/aktif kategori/üyelik ve oturum sahipliği kontrolleri korunur. Yayında sorusu olmayan testler kendi listesine hata mesajıyla döner; kendi adresine yönlenip döngü oluşturmaz. Başlatma/oynama yanıtları kullanıcıya özeldir; ara yönlendirme private, no-store döner.
- Çözüm ekranlarında içerik SEO başlık/açıklama şablonları uygulanmaya devam eder; kişisel oturumların noindex, nofollow ve canonical yokluğu korunur. Ara sayfası kaldırılan deneme/konu/animasyon çözüm URL'leri SiteSitemap tarafından dışlanır; herkese açık liste/kategori sayfaları mevcut SEO kurallarını kullanır. Arama sonuçları çözüm URL'sini açarak aynı başlangıç akışını kullanır.
- Son doğrulama: 348 test / 5976 assertion başarılı (130.13 saniye); gerçek MySQL kurulum testleri dahil, atlanan test yok. Başlık/form bağlantıları, doğrudan giriş, oturum sahipliği, yenilemede süre/cevapların korunması, boş testler, SEO ve sitemap kontrol edildi. Log: C:/xampp/ehliyetgo-backups/direct-test-start-20261103/full-tests-final.txt. Canlı veritabanı içerikleri değiştirilmedi; geçici hazırlık klasörü temizlendi.

### 3 Kasım 2026 — Ziyaretçi ve üye çözüm akışları

- Son kullanıcı kararı önceki devam davranışını değiştirir: başlangıç/tanıtım ara sayfası yoktur. Başlık, başlatma düğmesi ve doğrudan URL çözümü açar. Üyelik gerektiren denemelerin erişim sınırı korunur.
- Yeni ziyaretçi çözümleri GuestAttempts ile yalnız geçici Laravel oturumunda tutulur; exam_attempts / exam_attempt_answers tablolarına yazılmaz. guest-UUID kimlikleri oturuma bağlıdır, başka oturumdan açılamaz. Sonuç geçicidir; çıkış aktif çözümü siler. En fazla 10 geçici kayıt, iki saatlik geçerlilik sınırı vardır. Eski veritabanı kayıtlarına toplu silme yapılmadı.
- Üyenin konu/animasyon testi cevapları ve current_question_index veritabanında korunur. Aynı aktif test yeniden başlatılmak yerine devam eder. Eski expires_at alanı konu testinde süre sınırı olarak kullanılmaz. Dashboard devam kartı yalnız konu testlerini gösterir.
- Denemede devam özelliği yoktur: Çıkış, başka ön yüz sayfasına gitmek veya yeniden başlatmak önceki aktif denemeyi abandoned yapar; tamamlanma tarihi ve puan üretilmez. Profil, başarı, konu ve günlük raporlar yalnız tamamlanan sonuçları kullanır. Yeni ziyaret normal başlangıçtır; sayfa yenileme mevcut cevapları, soru konumunu ve gerçek bitiş zamanını korur. Tarayıcı navigation türü/freshStart kontrolü yeni ziyaret ile yenilemeyi ayırır; geri-ileri önbelleğinden eski deneme canlandırılmaz.
- Bitir POST'u veya açık denemede süre dolması sonuçlandırır. GET ile sınav listesine/çözüme/sonuca bakmak süresi geçmiş oturumu kendiliğinden tamamlamaz. Süreden sonra cevap yazılması reddedilir. Konu testi yalnız Bitir ile tamamlanır.
- Cevap, konum, çıkış ve bitirme istekleri sahiplik/CSRF korumasını kullanır. Ziyaretçi oturumu mutasyonları session blocking ile sıralanır; üye veritabanı güncellemelerinde mevcut transaction kontrolleri korunur. Yeni POST rotaları exams.position ve exams.leave'dir. Çıkış bekleyen cevap/konum kayıtlarını bekler. Sonuç ve çözüm yanıtları private, no-store ve noindex kalır.
- 2026_11_03_180000_add_attempt_question_position yalnız varsayılanı 0 olan unsigned current_question_index sütununu ekler. Panel modülü/rol kaydı değiştirilmedi. Çalışan sitede ayrı ziyaretçi oturumuyla doğrudan başlatma, yenileme, konum ve çıkış doğrulandı; üye hesabıyla canlı test yapılmadı.
- Son doğrulama: aktarılan projede php artisan test ile 355 test / 6082 assertion başarılı (291.60 saniye); gerçek MySQL kurulum testleri dahil, atlanan test yok. Ortak tarayıcı akışının node testleri 11/11 başarılı. Loglar: C:/xampp/ehliyetgo-backups/attempt-lifecycle-20261103/full-tests.txt ve isolated-js-tests-final.txt. Canlı ziyaretçi doğrudan giriş/yenileme/konum/çıkış kontrolü başarılı.

### 3 Kasım 2026 — Sunucuya geçiş tercihi: GitHub ve Plesk

- Kullanıcının onayladığı plan: Proje özel GitHub deposunda tutulacak; Plesk Git desteği üzerinden sunucuya alınacak. Bu karar yalnız gelecekteki kurulum/dağıtım planıdır; şu anda depo, bağlantı veya dağıtım oluşturulmadı.
- İlk kurulumda kullanıcı Plesk'ten boş MySQL veritabanı ve yetkili kullanıcı oluşturup bağlantı bilgilerini tarayıcı kurulum ekranına girecek. Yayın kökü public olacak; PHP bağımlılıkları, derlenmiş varlıklar, izinler ve gerekli zamanlanmış işler sunucuya uygun hazırlanacak.
- Sonraki kod değişikliklerinde yeniden kurulum yapılmayacak. GitHub'a gönderilen kodlar Plesk'ten çekilip dağıtılacak; varsayılan yaklaşım kontrollü elle dağıtım, otomatik dağıtım ayrıca kararlaştırılabilir.
- Dağıtımlar sunucudaki .env, APP_KEY, kurulum kilidi, yüklenen dosyalar ve mevcut veritabanını korumalıdır. Sırlar, veritabanı yedekleri ve kullanıcı verileri Git deposuna eklenmez. Yeni şema değişiklikleri yedek ve kontrol sonrasında uygun migration ile uygulanır; canlı veritabanında migrate:fresh/reset veya temiz kurulum çalıştırılmaz.
- Panelde Sistem Güncelleme bölümü istenmiyor; Git/Plesk dağıtımı buna gerek duymadan hazırlanacak. Sunucuya geçiş aşamasında Plesk Git yetkileri ve hosting olanakları doğrulanacak.


### 3 Kasım 2026 — Seçilen denetim düzeltmeleri

- Şifre yenileme `/sifremi-unuttum` ve `/sifre-yenile/{token}` yollarını kullanır. Laravel Password broker ile hashli, süreli ve tek kullanımlık token, CSRF ve istek sınırı vardır. CampaignMailer mevcut SMTP entegrasyonunu paylaşır; bu özellik SMTP'yi kendiliğinden etkinleştirmez. Linkin kökü yalnız app.url'dir. Başarılı sıfırlamada remember token yenilenir ve database session sürücüsünde kullanıcının mevcut oturumları silinir; otomatik giriş yapılmaz.
- Sonuç ve kaydedilen sorular açıklamaları QuestionMedia::clean ile temizlenip zengin içerik olarak gösterilir. Sonuç barajı sınavın passing_score değerinden gelir.
- Tailwind CDN kaldırıldı. Üç eski tema korunarak site/admin/admin-login CSS dosyaları Vite + Tailwind 3/PostCSS ile derlenir. Node 24 / pnpm 11: `pnpm install --frozen-lockfile`, `pnpm run build`. public/build dağıtıma dahil olmalı; Git'te ignored ise Plesk/CI derleme adımı bunu sağlamalıdır. Alpine yalnız Livewire üzerinden gelir.
- Filament paketleri ve kullanılmayan canAccessPanel metodu kaldırıldı; Livewire doğrudan Composer bağımlılığıdır. Eski welcome/about/profile şablonları ve erişilmeyen controller metotları, analyze_html.php ve yerel cookie dosyası kaldırıldı.
- Yeni migration seo_settings içindeki kullanılmayan site_title/meta_description/canonical_base ve exams/topic_tests question_count alanlarını kaldırır. Soru sayısı ilişki/pivot üzerinden türetilir; veritabanına ayrı sayaç yazmayın. NotificationCampaign.target ve AutomationRule.channels modern kayıtlarda JSON seçimlerinden hesaplanır. Tanınmayan eski metinleri kaybetmemek için nullable eski alanlar yalnız geçmiş kayıt yedeği olarak tutulur.
- 13 (hazırlık yüzdesi), 15 (yetim görseller) ve 22 (iki iletişim formu akışının birleştirilmesi) bu tur yalnız öneri kapsamındadır; uygulanmamıştır.
- Canlı aktarım ve son doğrulama: 365 PHP testi / 6172 assertion ve 11 JavaScript testi başarılı. Gerçek MySQL HTTP kurulum testleri dahil; atlanan test yok. Son log: C:/xampp/ehliyetgo-backups/selected-audit-fixes-20261103/final-full-tests.txt.
- 23. madde kapsamında yalnız eski ziyaretçi verisi temizlendi: 92 exam_attempts ve bunların 38 cevabı. 13 üye çözümü korundu. Kullanıcılar, içerikler ve diğer korunacak tabloların satır hashleri aktarım öncesi/sonrası eşleşti; .env değişmedi. Yeni ziyaretçi akışı yine yalnız geçici oturum kullanır.
- Yedekler proje dışında C:/xampp/ehliyetgo-backups/selected-audit-fixes-20261103 içindedir; before-deploy.sql canlı aktarım anındaki tam MySQL yedeği, code-before eski kod ve bağımlılıklardır. SMTP pasif bırakıldı; gerçek e-posta gönderilmedi. Gerçek sağlayıcıyla teslim testi SMTP bağlandıktan sonra yapılmalıdır.
- Site/admin derlenmiş CSS etiketi head sonunda kalır; eski CDN'nin öncelik sırasını korur ve site-button-primary gibi ortak sınıflara uygulanan utility renk/genişliklerini ezmez. Masaüstü panel/site, mobil şifre formu/menü ve canlı Livewire soru araması kontrol edildi.

### 3 Kasım 2026 — İletişim gönderimleri ortak servise bağlandı (22. madde)

- Kullanıcının onayıyla ContactController::submit ve Livewire ContactForm::submit aynı ContactMessageSubmission servisini kullanır. Alan kuralları, trim/boş telefon normalizasyonu, honeypot ve ContactMessage oluşturma tek noktadadır. Ortak sınırlar mevcut görünen formu esas alır: ad 2–100, e-posta en fazla 150, telefon isteğe bağlı/en fazla 30, konu 3–200, mesaj 10–3000 karakter.
- Geçerli her gönderim bir okunmamış mesaj oluşturur; istemciden is_read kabul edilmez. Honeypot doluysa kayıt oluşmaz. Hata düzeltildikten sonra Livewire eski doğrulama mesajlarını temizler. Görünüm ve paneldeki liste/detay akışı korunur; SMTP veya e-posta gönderimi eklenmedi.
- ContactMessageFlowTest iki giriş yolunda aynı sınırları, tek kayıt oluşmasını, panel erişimini, normalizasyonu, honeypot ve hata sonrası toparlanmayı sınar. Tam test paketi: 369 test / 6281 assertion başarılı, atlanan test yok; gerçek MySQL HTTP kurulum testleri dahil. Canlı iletişim sayfası HTTP 200 doğrulandı. Mevcut mesajlar/veritabanı değiştirilmedi.
- Kod yedeği ve test kaydı: C:/xampp/ehliyetgo-backups/contact-unify-20261103. Geçici hazırlık dosyaları temizlendi.

### 3 Kasım 2026 — Öğrenci portalı tasarımı

- /dashboard sitenin ortak kart, renk, buton ve genişlik yapısıyla yenilendi. Üstte gerçek profil ve sınav tarihi, devam edilen konu testi, hazırlık göstergesi ve çalışma özeti; altta kategori ilerlemeleri, deneme/kaydedilen soru bağlantıları, öğrenme ve Premium tekrar alanları bulunur.
- Sınav tarihi boşsa örnek tarih gösterilmez; hesap ayarlarına Sınav tarihini ekle bağlantısı görünür. İlk sonucu olmayan üyede ortalama yerine boş durum gösterilir. Cevap vermeyen üyeye başarı iddiası gösterilmez.
- Mevcut istatistik hesaplamaları, erişim yetkileri, kaldığı yerden devam ve Premium POST/CSRF akışı korunur. Tasarım stilleri öğrenci portalına kapsamlanmıştır; başka sayfaları etkilemez. İkonlar yerel Blade SVG bileşeninden gelir.
- Masaüstü ve 390px mobil görünüm canlı tarayıcıda doğrulandı; yatay taşma veya konsol hatası yok. Kod yedeği ve ekran görüntüleri: C:/xampp/ehliyetgo-backups/student-portal-design-20261103.
- Son doğrulama: 370 test / 6296 assertion başarılı (119.63 saniye), atlanan test yok; gerçek MySQL kurulum senaryoları dahil. Kayıt: C:/xampp/ehliyetgo-backups/student-portal-design-20261103/verified-tests.txt. Geçici hazırlık klasörü temizlendi.

### 3 Kasım 2026 — Üst menü açılır alanları

- Bildirimler ve kullanıcı hesabı details öğeleri header-account-menu grubunu paylaşır; aynı anda yalnız biri açık kalır. Mevcut Alpine üzerinden dışarı tıklanınca kapanır, Esc açık alanı kapatıp odağı kendi düğmesine döndürür. Yeni bağımlılık veya veri değişikliği yoktur.
- Her iki yönde menü değiştirme, dışarı tıklama, Esc ve 390px mobil geçiş canlı tarayıcıda doğrulandı. Kod yedeği: C:/xampp/ehliyetgo-backups/header-menus-20261103.
- Tam test paketi: 370 test / 6296 assertion başarılı (102.02 saniye); gerçek MySQL kurulum testleri dahil, atlanan test yok. Kayıt: C:/xampp/ehliyetgo-backups/header-menus-20261103/tests.txt.

### 3 Kasım 2026 — Çalışma sayfalarında ortak özet

- study-summary Blade bileşeni deneme sınavları, konu testi kategorileri, kategori içi test listesi, animasyonlu sorular, konu anlatımı ve trafik işaretleri başlıklarında kullanılır. Toplam ve tamamlanan/öğrenilen değerler ilgili sayfanın içeriklerinden hesaplanır.
- Konu testleri normal ve animasyonlu bölümleri ayrı sayar; taslaklar ve pasif kategoriler dışarıda kalır. Üyenin tamamladığı farklı testler sayılır; tekrar çözüm sayıyı artırmaz, devam eden yeniden çözüm eski tamamlanmayı düşürmez. Ziyaretçilerde test geçmişi kaydedilmediği için tamamlanan 0 gösterilir. İşaret ve konu öğrenme özeti mevcut kullanıcı/oturum ilerleme kurallarını kullanır.
- Masaüstü ve mobil yerleşim, alt kategori ve animasyonlu bölüm sayaçları kontrol edildi. Testler StudySummaryTest içinde tekrar, taslak, bölüm ve kullanıcı ayrımını kapsar. Geçici düzenleme betiği silindi. Yedekler: C:/xampp/ehliyetgo-backups/study-summary-20261103.

### 3 Kasım 2026 — Site işlem düğmeleri ortak ikon tasarımı

- action-label ve mevcut student-icon bileşeni site işlem düğmelerinde ortak ikon/metin hizası sağlar. Çalışma için kitap, başlat/devam için oynat, sonuç için grafik, bitir için bayrak, tekrar/geri/ileri/kaydet/gönder için uygun SVG kullanılır. Ekran okuyuculara metinler korunur; yalnız ikonlu tekrar düğmelerinde aria-label bulunur.
- Deneme ve konu testleri, animasyonlu sorular, konu anlatımı, trafik işaretleri, sonuç ekranları, ana sayfa çalışma bağlantıları, blog, arama/404, profil ve giriş formları uyarlandı. Ana işlem düğmeleri en az 44px yüksekliğinde ve klavye odağı görünürdür. Admin paneli etkilenmez.
- Soru gezinmesinde Alpine x-text düğmenin içindeki metin alanına taşındı; sonraki/bitir değişiminde ikon korunur. Canlı ziyaretçi testinde ileri, geri, son soruda bitirme onayı, devam ve çıkış kontrol edildi. Mobil taşma ve tarayıcı konsol hatası yok. HTTP işlemleri, form hedefleri ve izinler korunur.
- Ön doğrulama: 21 ilgili PHP testi / 199 assertion ve 11 JavaScript testi başarılı. Kod yedeği ve görsel: C:/xampp/ehliyetgo-backups/site-action-icons-20261103. Geçici düzenleme betiği temizlendi.
- Son tam doğrulama: 372 PHP testi / 6319 assertion başarılı (146.20 saniye); gerçek MySQL kurulum testleri dahil, atlanan test yok. Kayıt: C:/xampp/ehliyetgo-backups/site-action-icons-20261103/tests.txt.

### 4 Kasım 2026 — Tıklanabilir deneme ve test kartları

- Deneme sınavları, konu testleri ve animasyonlu sorular listelerinde alt başlatma düğmesi satırı kaldırıldı. Kartın tamamı ana işlem alanıdır; sağda yön oku bulunur. Başlıklar dar ekranda satıra bölünebilir.
- study-card-action bileşeni POST/CSRF ile başlatmayı, devam bağlantısını, sonuç bağlantısını ve ziyaretçide üyelik bağlantısını ortaklaştırır. Tamamlanan kartın tekrar çözme ikonu ayrı formdur; ana bağlantının içine gömülmez.
- Klavye odağı görünürdür; eylemler içerik başlığıyla birlikte ekran okuyucuya aktarılır. Tekrar ikonu 44px dokunma alanına sahiptir. Hareket azaltma tercihi desteklenir.
- Masaüstü ve 390px mobil görünüm, karttan sonuç açma, Enter ile erişim ve tekrar düğmesinin ayrı tıklama alanı tarayıcıda doğrulandı. Canlı çözüm geçmişi değiştirilmedi. Mevcut TestStartButtonsTest yeni kart eylemlerini, CSRF ve devam/sonuç hedeflerini doğrular.
- Kod yedeği, test kayıtları ve ekran görüntüleri: C:/xampp/ehliyetgo-backups/clickable-study-cards-20261103. Geçici düzenleme betiği silindi.
- Son tam doğrulama: 372 test / 6325 assertion başarılı (107.47 saniye); gerçek MySQL kurulum testleri dahil, atlanan test yok. Kayıt: clickable-study-cards-20261103/verified-tests.txt.

### 4 Kasım 2026 — Çalışmaya başla ikon tasarımı

- Trafik İşaretleri ve Konu Anlatımı listelerindeki yazılı Çalışmaya Başla alanları, ortak study-entry-icon bileşeniyle 44px yuvarlatılmış ok ikonuna çevrildi. Üzerine gelince veya klavye odağında marka rengine geçer. Kartın tamamı mevcut öğrenme bağlantısıdır; erişilebilir adı kategori ve işlem bilgisini korur.
- Konu Anlatımı mobil kartında ikon başlığın sağında, ilerleme alanı alt satırda yer alır. Masaüstü ve 390px mobil görünüm, taşma ve trafik işaretleri öğrenme bağlantısı tarayıcıda doğrulandı. Veri değişikliği yapılmadı; geçici düzenleme betiği temizlendi.
- Kod yedeği ve görsel: C:/xampp/ehliyetgo-backups/study-entry-icon-20261104.
- Tam test paketi: 372 test / 6325 assertion başarılı (125.08 saniye); gerçek MySQL kurulum testleri dahil. Kayıt: study-entry-icon-20261104/tests.txt.

### 4 Kasım 2026 — Tek üye girişi bağlantısı

- Ortak site üst menüsünde ve mobil açılır menüde Giriş Yap/Kayıt Ol çifti yerine login rotasına giden Üye Girişi bağlantısı bulunur. Giriş formundaki kayıt bağlantısı korunur.
- Masaüstü/mobil görünüm ve giriş sayfasına geçiş tarayıcıda doğrulandı. Mevcut ziyaretçi başlık testi yeni etiketi, login hedefini ve üst menüde register bağlantısının kaldırılmasını doğrular.
- Kod yedeği ve ekran görüntüsü: C:/xampp/ehliyetgo-backups/member-entry-20261104. Geçici betik temizlendi.
- Tam test paketi: 372 test / 6326 assertion başarılı (94.25 saniye); gerçek MySQL kurulum testleri dahil. Kayıt: member-entry-20261104/tests.txt.

### 4 Kasım 2026 — Sayfa yollarının sadeleştirilmesi

- Kullanıcı onayıyla giriş/kayıt, öğrenci portalı ana ekranı, altı ana içerik listesi, iletişim, kurumsal sayfa, üye profili ve sınav sonucu şablonlarından breadcrumb kaldırıldı. Sonuç ekranındaki mevcut listeye dönüş işlemleri korundu.
- Hesap ayarları ve kaydedilen sorularda yol Öğrenci Portalı ile başlar. Yazar profilinde Blog > yazar adı kullanılır. Konu testi kategorisi, konu anlatımı kategorisi/detayı ve blog yazısı yolları korunur.
- Trafik işaretleri öğrenme ekranında breadcrumb yerine Kategorilere dön bağlantısı kullanılır. Kaldırılan portal breadcrumb CSS kuralları temizlendi.
- İletişim/giriş görünümü, öğrenme ekranından kategoriye dönüş, mobil yazar ve test kategorisi yolları tarayıcıda doğrulandı. Veritabanı ve adresler değiştirilmedi. Geçici düzenleme betiği temizlendi.
- Kod yedeği ve görünüm: C:/xampp/ehliyetgo-backups/breadcrumbs-20261104.
- Tam test paketi: 372 test / 6326 assertion başarılı (103.29 saniye); gerçek MySQL kurulum testleri dahil. Kayıt: breadcrumbs-20261104/tests.txt.

### 4 Kasım 2026 — Panelden yönetilen sayfaların ön yüz tasarımı

- Ortak pages/page şablonu site arka planı üzerinde 960px genişliğe kadar beyaz kart, hafif marka rengi başlık alanı, kısa açıklama ve takvim ikonlu tarih ile yeniden düzenlendi. Görüntüleme sayısı ve ayırıcı nokta ön yüzden kaldırıldı; panel sayfa görünümlerinde de bu bilgi bulunmadığı kontrol edildi.
- İçerikte başlık, paragraf, liste, alıntı, bağlantı, tablo ve medya için bu sayfalara özel okunabilir stiller tanımlandı. Dar ekranlarda kart boşlukları küçülür, geniş tablolar kendi içinde kayar. İlk yayın/son güncelleme seçimi, panel içeriği ve SEO davranışı korundu. Veri alanı silinmedi.
- Hakkımızda masaüstü ve SSS mobil görünümü canlı tarayıcıda doğrulandı; mobil yatay taşma yok. Geçici düzenleme betiği temizlendi. Kod yedeği, görsel ve test kaydı: C:/xampp/ehliyetgo-backups/managed-page-design-20261104.
- Tam test paketi: 372 test / 6326 assertion başarılı (131.13 saniye); gerçek MySQL kurulum testleri dahil. Kayıt: managed-page-design-20261104/tests.txt.

### 4 Kasım 2026 — Sınav/test ekranı kullanılabilirlik düzeltmeleri

- Kullanıcının onayladığı 2, 3, 5, 6, 7, 8. maddeler uygulandı. Cevap, konum ve çıkış istekleri ortak 15 saniyelik abort/zaman aşımı ile sınırlıdır. Bekleyen konum isteği kuyrukta sonsuza kadar kalmaz; hatalar yeniden denenebilir. Başarılı cevap kaydında bildirim gösterilmez, hatalar tek kırmızı alanda görünür ve başarılı tekrar sonrası temizlenir.
- Denemeden ve ziyaretçi testinden çıkışta veri kaybını açıklayan onay bulunur. Üyenin konu testi kaldığı yerden devam ettiği için doğrudan çıkışı korunur. Sonraki/Önceki arasındaki tekrar cevap sayacı kaldırıldı.
- Soru haritası ortak test-question-map bileşenidir; 1024px altında kapalı başlayıp açılır/kapanır, masaüstünde açık kalır. Soru düğmelerinin erişilebilir cevap durumu ve aktif soru bilgisi bulunur.
- Bitirme/çıkış pencereleri ortak test-session-dialogs bileşeninde yerel dialog kullanır. İlk odak güvenli vazgeç/devam işlemindedir; Tab/Shift+Tab içeride döner, Escape kapatır, odak tetikleyiciye döner. Bitirme sırasında ikinci gönderim ve kapatma engellenir.
- Kullanıcının 4. madde talebi inceleme olarak karşılandı: kaydetme/kaldırma uç noktası, saved_questions ve kaydedilen sorular sayfası vardır; deneme ekranında düğme bulunur, konu/animasyonlu testte bulunmaz. Bu aşamada kaydetme özelliği genişletilmedi.
- 1. madde (arka planda sayaç sapması) örnekle açıklandı; kullanıcı yalnız açıklama istediğinden zamanlayıcı değiştirilmedi.
- Canlı mevcut konu testinde mobil harita, bitirme onayı ve klavye odağı doğrulandı. Cevap verilmedi ve test bitirilmedi; geçici soru gezinmesi sonrası özgün 23. soruya dönülüp yenilemede doğrulandı. 16 JavaScript testi başarılı. Yedek/görseller: C:/xampp/ehliyetgo-backups/exam-usability-20261104. Geçici düzenleme betiği silindi.
- Tam PHP test paketi: 372 test / 6326 assertion başarılı (158.45 saniye); gerçek MySQL kurulum testleri dahil. Kayıt: exam-usability-20261104/tests.txt.

### 4 Kasım 2026 — Kaydedilen sorular ve deneme sayacı

- Kullanıcının onayladığı 4. madde: ortak question-bookmark bileşeni deneme, konu testi, animasyonlu test ve sonuç ekranlarına eklendi. Üyeler soruyu kaydedebilir/kaldırabilir; Kaydettiğim Sorular sayfasından da kaldırabilir. Son kayıt kaldırılınca boş durum görünür. Ziyaretçilere kayıt düğmesi gösterilmez.
- Ortak bookmark isteği açık hedef durum gönderir; kullanıcı bazında kilitlenen işlem tekrarlandığında çift kayıt oluşturmaz. Bekleyen düğme devre dışıdır, zaman aşımı ve hata durumunda mevcut görünüm korunur ve yeniden denenebilir. Başarılı kayıtta ayrıca mesaj gösterilmez.
- Kullanıcının onayladığı 1. madde: deneme sayacı her kontrolde sunucunun expires_at bitiş anı ile mevcut zaman farkını hesaplar; istemci/sunucu saat farkı dengelenir. Geciken aralıklar ve sekmeye dönüş kalan süreyi düzeltir, süre dolmuşsa mevcut otomatik bitirme akışı çalışır. Konu testleri süresiz kalır.
- Canlı tarayıcıda mevcut 126 numaralı soru kaydedildi, listede görüldü, tekrar kaldırıldı ve yenilemede kaldırıldığı doğrulandı. Önceden kaydedilmiş sorular korundu; cevap veya çözüm geçmişi değiştirilmedi. Masaüstü ve 390px mobil görünüm kontrol edildi. Geçici betikler temizlendi.
- Doğrulama: 376 PHP testi / 6372 assertion başarılı (109.08 saniye), gerçek MySQL kurulum testleri dahil. 21 JavaScript testi başarılı; tekrar istekleri, hata/zaman aşımı ve geciken/arka plan sayaç kontrolleri kapsanır.
- Kod yedeği, test kaydı ve ekran görüntüsü: C:/xampp/ehliyetgo-backups/bookmarks-timer-20261104.
### 4 Kasım 2026 — Mobil soru haritası başlık düğmesi

- Deneme, konu testi ve animasyonlu testlerde mobil Cevaplandı/Boş etiketi yerine ikonlu Soru Haritası düğmesi eklendi. Harita soru başlığının altında açılır; alttaki eski mobil harita kaldırıldı. Masaüstü sağ harita ve durum etiketi korunur.
- Ortak test-question-map bileşeni mobil/masaüstü görünümünü paylaşır. Cevaplanan, boş ve aktif soru stilleri korunur. Numara seçimi mevcut goToQuestion/savePosition akışını kullanır; harita kapanır ve odak açma düğmesine döner. Escape ile kapatma, aria-expanded/controls ve 44px dokunma alanları bulunur.
- Masaüstü ve 390px mobil görünüm, soru seçimi ve kapanma canlı tarayıcıda kontrol edildi. Geçici gezinmeden sonra özgün 24. soruya dönüldü, yenilemede doğrulandı. Cevaplar değiştirilmedi; test bitirilmedi. Geçici düzenleme betiği kaldırıldı.
- Kilit dosyasındaki geliştirme bağımlılıkları pnpm install --frozen-lockfile ile kuruldu; pnpm run build başarılı. 376 PHP testi / 6372 assertion (132.05 saniye) ve 21 JavaScript testi başarılı.
- Kod yedeği, test kaydı ve mobil görüntü: C:/xampp/ehliyetgo-backups/mobile-question-map-20261104.
### 4 Kasım 2026 — Soru başlığındaki durum etiketinin kaldırılması

- Kullanıcı isteğiyle deneme, konu testi ve animasyonlu test soru başlığındaki Cevaplandı/Boş etiketi kaldırıldı; artık kullanılmayan isCurrentAnswered getter temizlendi. Soru haritasının renkleri ve erişilebilir cevap durumları korundu.
- 376 PHP testi / 6372 assertion (126.46 saniye) ve 21 JavaScript testi başarılı. Tarayıcı kontrolü bağlantı hatası nedeniyle bu küçük değişiklikte tamamlanamadı; şablon ve test doğrulaması yapıldı.
- Yedek ve test kayıtları: C:/xampp/ehliyetgo-backups/remove-answer-status-20261104.
### 4 Kasım 2026 — Test ilerleme yüzdesi

- Ortak test-session-bar içinde ilerleme çubuğunun yanına cevaplanan/toplam soru oranını gösteren yüzde eklendi. Deneme, konu testi ve animasyonlu testlerde otomatik güncellenir; sıfır soru için %0, üst sınır %100 kullanılır. Cevapların doğruluğunu değil cevaplama ilerlemesini gösterir.
- 376 PHP testi / 6372 assertion (102.52 saniye), 21 JavaScript testi başarılı. Tarayıcı bağlantısındaki browser.user hatası nedeniyle canlı görsel kontrol tamamlanamadı.
- Yedek ve test kayıtları: C:/xampp/ehliyetgo-backups/progress-percent-20261104.
### 4 Kasım 2026 — Trafik işaretleri öğrenme alanı

- Trafik işaretleri testlerden bağımsız öğrenme alanı olarak yenilendi. Üst öğrenme çubuğu; çıkış, kategori adı, öğrenilen/toplam ve yüzde içerir. Tümü/Öğrenmediklerim/Tekrar Edeceklerim filtreleri, gerçek levha görseli, mevcut durum, Öğrendim/Tekrar Et ve önceki/sonraki gezinme bulunur. Tek ortak işaret haritası masaüstünde sağda, mobilde düğmeyle kart içinde açılır. Seçimde kapanır; klavye odağı düğmeye döner.
- Üyelerin durumları user_sign_reviews içinde, son işaret ve filtre yeni user_sign_learning_progress tablosunda kullanıcı/kategori bazında tutulur. Durum ve sonraki konum tek transaction içinde kaydedilir; üye satırı kilidi yinelenen isteklerin çift kayıt oluşturmasını engeller. Taslak veya farklı kategoriye ait işaret konumu reddedilir.
- Ziyaretçi ilerlemesi yalnız sekmeye ait sessionStorage içinde tutulur; yeni ziyaretçi kaydı veritabanına yazılmaz. Eski ziyaretçi verisi bu işlemde silinmedi, yeni akışta okunmaz. Kayıt uçları auth korumalıdır. Tarayıcı kaydı engelliyse çalışma devam eder ve kalıcılık uyarısı görünür.
- İstekler 15 saniye ile sınırlıdır; bekleyen işlem sırasında çift tıklama/gezinme engellenir. Hatalarda kart ve durum değişmez, açık hata ve yeniden deneme vardır. Başarı bildirimi/toast yoktur. Tamamlanan kategori, boş kategori ve boş filtre durumları işlendi.
- Panelde PNG/JPG/WebP yükleme (2 MB, 4096px sınırı), önizleme, kaldırma ve Taslak/Yayında seçimi eklendi. Yeni levhalar taslak başlar; mevcut kayıtlar migration ile yayında korunur. Dosyalar public disk traffic-signs altında saklanır. Başarılı değiştirmede yalnız bu modülün sahip olduğu ve başka kayıtta kullanılmayan eski dosya temizlenir. SVG yükleme desteklenmez; eski ham svg_icon içeriği HTML olarak çalıştırılmaz.
- Panel listelemesine yayın filtresi/durumu eklendi. Taslak levhalar ön yüz, kategori ilerlemesi ve öğrenci portalındaki sayılara dahil edilmez. Yönetim modülü eklenmedi; mevcut trafik işaretleri izinleri korunur.
- Mevcut 33 levhanın 15 görseli boş, 18 görseli aynı Twemoji örnek ünlem adresiydi. Bu yanıltıcı örnek görsel öğrenme ekranında kullanılmaz; gerçek medya bulunmadığında Görsel eklenmemiş görünür. Gerçek levha dosyaları panelden yüklenmelidir. Bu aşamada levha kodları/açıklamaları resmî içerik denetiminden geçirilmedi; eski doğrulanmamış standart uygunluğu metni kaldırıldı.
- 2026_11_04_120000_extend_traffic_sign_learning migration canlı MySQL üzerinde uygulandı. Öncesinde üç trafik tablosu proje dışına data-before.json olarak yedeklendi. 33 levha korundu; öğrenme durumlarının yedekle birebir aynı kaldığı kontrol edildi. Canlı tarayıcıda masaüstü/mobil, boş tekrar filtresi, harita, kayıtlı konumun yenilemede korunması ve başarısız isteğin tekrar denenmesi doğrulandı. Başlangıçtaki ilk işarete dönüldü; öğrenme durumu işaretlenmedi.
- Son doğrulama: 384 PHP testi / 6435 assertion (134.30 saniye), gerçek MySQL kurulum testleri dahil; 29 JavaScript testi başarılı. pnpm run build başarılı. Geçici düzenleme betikleri temizlendi.
- Kod/veri yedeği, test kayıtları ve ekran görüntüsü: C:/xampp/ehliyetgo-backups/traffic-learning-20261104.
## 4 Kasım 2026 — Üye paneli tarihleri ve hesap ayarları
- Yerel .env ve .env.example APP_LOCALE=tr olarak düzeltildi. Kurulum zaten tr üretir. Tarih adları ve diffForHumans ifadeleri Türkçe görünür; fallback dili değiştirilmedi.
- Üye hesap ayarlarında doğum tarihi sunucuda before_or_equal:today ile, formda max ile sınırlandırılır. Gelecek tarihler Türkçe hata verir; boş tarih kabul edilir.
- Ehliyet sınıfında boş Seçiniz seçeneği vardır. Önceden kaydedilmiş listede olmayan değer seçenek olarak korunur; otomatik B seçilmez. 2026_11_04_190000 migration alanı nullable/default null yaptı ve canlı MySQL üzerinde uygulandı; mevcut değerler değişmedi.
- MemberSettingsValidationTest: 3 test/22 assertion. Tüm paket: 384 başarılı, MySQL ortamı gerektiren 3 test ayrı çalıştırıldı. InstallationTest 6/6 başarılı; toplam 387 farklı test başarılı. Canlı ekranda 06 Kasım 2026 ve 22 saat önce doğrulandı. Canlı üye profil bilgileri değiştirilmedi.
- Yedekler ve test kayıtları: C:/xampp/ehliyetgo-backups/member-settings-20261104.
## 4 Kasım 2026 — Deneme sınavı kart tasarımı
- /deneme listesi iki sütunlu numaralı kartlarla yenilendi; 767px altında tek sütun olur. Kartın tamamından mevcut başlatma/sonuç aksiyonu kullanılır, tekrar çöz düğmesi ayrı kalır. Üyelik kısıtı ve sınav akışı değiştirilmedi.
- Tamamlanan sınavlarda doğru/yanlış/boş ve puan ayrı alt satırda gösterilir. Soru sayısı, süre ve baraj alanları geri eklenmedi. CSS yalnız exam-catalog altında kapsamlıdır.
- Masaüstü ve 390px mobil görünüm kontrol edildi. pnpm run build başarılı. artisan test: 384 başarılı / 6412 assertion, ortam değişkenleri verilmediği için 3 MySQL kurulum testi atlandı; bu tasarım çalışmasında yeniden çalıştırılmadı. Önceki üye ayarı çalışmasında MySQL kurulum testleri başarılıydı.
- Yedek ve test kayıtları: C:/xampp/ehliyetgo-backups/exam-cards-20261104.
## 4 Kasım 2026 — Öğrenme listelerinde ortak kart tasarımı
- Deneme, konu testi ve animasyonlu soru listeleri study-catalog-card üzerinden aynı numaralı kart düzenini kullanır. study-card-action ile POST başlatma, sonuç, tekrar ve konu testlerinde devam bağlantıları korunur.
- Konu testi kategorileri, konu anlatımı kategorileri/ders listeleri ve trafik işareti kategorileri study-link-card kullanır. Kategorilerde ikon, derslerde numara; mevcut tamamlanma ve öğrenme bilgileri alt satırda korunur. Trafik işaretlerinde üye ve misafir yüzdelerinin Alpine bağları korunmuştur.
- Ortak stiller study-catalog-styles içinde; masaüstü iki sütun, 767px altında tek sütun. Önceden kaldırılan alt açıklama, soru/süre/baraj alanları geri eklenmedi.
- Derleme başarılı. artisan test: 384 başarılı / 6412 assertion; 3 ayrı MySQL kurulum testi ortamı verilmediğinden atlandı. Canlı konu testi, animasyon, konu kategorisi/ders listesi ve işaret kategorileri kontrol edildi; mobilde yatay taşma yok.
- Yedekler: C:/xampp/ehliyetgo-backups/shared-cards-20261104.
## 4 Kasım 2026 — Geri alınabilir anasayfa tasarımı
- Yalnız resources/views/pages/home.blade.php yenilendi. Üstte iki sütunlu tanıtım ve mevcut animasyon görseli, altta ortak study-link-card ile dört çalışma alanı bulunur. Mobilde tek sütun; mevcut bağlantılar ve CSRF korumalı hızlı deneme POST formu korunur. Veritabanı ve kontrolcü değiştirilmedi.
- Eski anasayfa: C:/xampp/ehliyetgo-backups/home-design-20261104-220703/home.blade.php. Kullanıcı eski görünümü isterse yalnız bu dosyayı resources/views/pages/home.blade.php üzerine geri yükleyip pnpm run build çalıştırın. Önceki ortak kart geliştirmelerini geri almayın.
- pnpm run build başarılı. artisan test: 384 başarılı / 6412 assertion, 3 MySQL kurulum testi ortamı verilmediğinden atlandı. Masaüstü ve 390px mobil kontrol edildi; yatay taşma yok. Test kaydı aynı yedek klasöründe tests.txt.
## 4 Kasım 2026 — Kurumsal sayfa ve iletişim genişlikleri
- Panelden yönetilen ortak pages/page şablonunun 960px sınırı kaldırıldı; site-container ile üst menü ve alt bilgi içeriğine hizalanır. Hakkımızda, SSS, gizlilik ve kullanım koşulları dahil bu şablonu kullanan sayfalarda başlık/beyaz kart ve iç boşluklar ortak tasarıma uyar. İçerik, tarihler ve SEO korunur.
- İletişim başlığı tam genişlikte kart; altında iletişim bilgileri ve mevcut Livewire formu 1:2 sütunlarda, 767px altında tek sütun olur. Form alt aksiyonları dar ekranda dikey dizilir. Telefonun ayar boşken gizlenmesi ve gönderim işleyişi korunur.
- pnpm run build başarılı; artisan test 384 başarılı / 6412 assertion, 3 MySQL kurulum testi ortamı verilmediğinden atlandı. Masaüstü hizaları ve 390px mobilde yatay taşma olmadığı doğrulandı.
- Önceki üç görünüm ve test kaydı: C:/xampp/ehliyetgo-backups/public-pages-20261104.
## 4 Kasım 2026 — Blog ortak site tasarımı
- Blog liste başlığı site-container içindeki beyaz kartla diğer liste sayfalarına hizalandı. Kategori filtreleri saran, klavyeyle erişilebilir ve seçili durumu aria-current ile belirtilen düğme biçimli bağlantılardır.
- Blog ve yazar yazı listeleri ortak blog-post-card bileşenini kullanır. Büyük harfli boş görsel alanı kaldırıldı; ikon/kategori, başlık, özet, yazar/tarih ve dairesel ok bulunur. Kartın tamamı tek yazı bağlantısıyla açılır. Masaüstü iki, 767px altında tek sütun; boş liste tüm satırı kaplar.
- Yazı detayının başlık arka planı ve iç boşlukları siteyle eşitlendi. Mevcut içerik, tarih/yazar, ilgili yazılar, paylaşım ve SEO akışı korundu. Profil bileşeni ve veritabanı değiştirilmedi.
- pnpm run build başarılı. artisan test: 384 başarılı / 6412 assertion, ayrı MySQL kurulum ortamı verilmediğinden 3 test atlandı. Canlı liste, kategori filtresi, yazı/yazar geçişi ve 390px mobil taşma kontrolü tamamlandı.
- Önceki blog görünümleri ve test kayıtları: C:/xampp/ehliyetgo-backups/blog-layout-20261104. Geri almada bu üç görünümü geri yükleyip derleme çalıştırın; yeni blog-styles/blog-post-card bileşenleri yalnız bu görünümlerden kullanılır.
## 4 Kasım 2026 — Blogda klasik kart düzenine dönüş
- Kullanıcı önceki blog görünümünü tercih etti. Son iki sütunlu ikonlu liste yerine üç sütunlu kapak alanlı kartlar, ortalanmış başlık, yuvarlak kategori filtreleri ve Yazıyı oku metni uygulandı. 1023px altında iki, 639px altında tek sütun olur. Kapak alanı önceki sistemde olduğu gibi başlık ilk harfini gösterir; görsel yükleme özelliği eklenmedi.
- Blog/yazar listeleri ortak blog-post-card kullanmaya devam eder. Site genişliği, mobil taşma düzeltmeleri, yazar/tarih ve erişilebilir tek kart bağlantısı korunur. Yazı detayı blog-layout-20261104 yedeğinden önceki görünümüne alındı.
- Değişiklik öncesi son tasarım ve test kaydı: C:/xampp/ehliyetgo-backups/blog-classic-20261104. Derleme ve masaüstü/390px mobil görünüm kontrolü başarılı.
## 4 Kasım 2026 — Blog yazısı okuma ekranı
- Yalnız pages/blog/show görünümü yenilendi. Başlık/kategori/okuma süresi/özet/yazar/tarihler sitenin ortak genişliğinde ayrı başlık kartında; yazı gövdesi ve sağdaki yazar/ilgili içerikler altında iki sütundadır. Mobilde tek sütun, başlık ve tarihler saran düzen kullanır.
- Liste ve yazar sayfalarının kullanıcının tercih ettiği üç sütunlu klasik kart tasarımı değiştirilmedi. İçerik, yayın tarihleri, SEO, yazar bağlantısı, görüntülenme ve paylaşım davranışı korundu.
- Derleme başarılı; masaüstü ve 390px mobil yerleşim kontrol edildi. Önceki yazı görünümü ve test kaydı C:/xampp/ehliyetgo-backups/blog-reading-20261104 altında. Geri almada yalnız show.blade.php geri yüklenip pnpm run build çalıştırılır.
## 13 Kasım 2026 — Konu anlatımı okuma tasarımı
- pages/lectures/show ortak site genişliğine, blog okuma ekranıyla uyumlu başlık ve okuma kartına uyarlandı. Masaüstünde çalışma durumu/sonraki konu sağda, mobilde yazının altında yer alır. Yinelenen tamamlama formu kaldırıldı; tek POST/CSRF formu ve mevcut ilerleme davranışı korunur.
- Zengin içerik stilleri lecture-content ile sınırlıdır; liste, görsel/video, tablo ve alıntı stilleri eklendi. Kategoriye dönüş bağlantısı eklendi. Son konuya ulaşmak bütün konuların tamamlandığı anlamına gelmediği için son sayfadaki yanlış tebrik ifadesi nötr çalışma devamı metniyle değiştirildi.
- 1280px masaüstü ve 390px mobil yerleşim kontrol edildi; yatay taşma yok. Derleme başarılı. Önceki şablon ve test çıktısı C:/xampp/ehliyetgo-backups/lecture-reading-20261113 altında; geri almak için show.blade.php geri yüklenip pnpm run build çalıştırılır.
- Tam test paketi: 384 başarılı, 6412 assertion; ortam gerektiren 3 MySQL kurulum testi atlandı (108.28 saniye).

## 13 Kasım 2026 — Yalnız onaylanan UI maddeleri 2, 5 ve 9
- Kategori ikonları StudyCategoryIcon üzerinden kategori adına göre ayrılır; konu testi, konu anlatımı ve işaret kategorileri uygun ikonları kullanır. Kayıtlı kategori adları/slugları değiştirilmez.
- BlogPostForm sağ sütununa Öne Çıkan Görsel eklendi: JPEG/PNG/WebP, en fazla 2 MB ve 4096x4096, önizleme/değiştirme/kaldırma; kayıt düzenlemede sayfada kalır. Dosyalar public disk blog-images altında, yol blog_posts.featured_image_path alanında tutulur. Blog/yazar kartları ve yazı detayı görseli gösterir. Eski görselsiz kayıtlar korunur. Görsel değişimi mevcut içerik tarihi/son düzenleyen takibine dahildir. Değiştirilen/silinen kapak yalnız güvenli klasörde ve başka blog kapağı/içeriğinde kullanılmıyorsa temizlenir.
- Konu anlatımında önceki/sonraki yayımlanmış kardeş konu order/id sırasıyla seçilir. LectureReadingContent üç veya daha fazla dolu h2/h3 başlığında açılır İçindekiler üretir; Türkçe metin ve benzersiz mevcut bağlantılar korunur, veritabanındaki içerik yeniden yazılmaz. Kısa yazılar değişmeden gösterilir.
- Yalnız bu maddeler uygulandı; diğer UI önerileri uygulanmadı. BlogFeaturedImageTest ve LectureReadingNavigationTest 5 test/63 assertion başarılı. Tam paket: 389 başarılı, 6475 assertion, ortam gerektiren 3 MySQL kurulum testi atlandı. Derleme ve canlı panel/kategori/mobil gezinme kontrolleri başarılı. Mevcut canlı konu yazılarının hepsi üçten az alt başlığa sahip; içindekiler uzun örnek içerikle izole testte doğrulandı.
- Önceki dosyalar ve test çıktısı: C:/xampp/ehliyetgo-backups/ui-selected-20261113. Kapak sütununu ekleyen 2026_11_13_190000 migration yerel MySQL üzerinde uygulandı; mevcut yazılara örnek görsel/içerik eklenmedi.
## 13 Kasım 2026 — Öğrenci genel bakış düzeni
- pages/student/dashboard yeniden düzenlendi: kompakt devam etme alanı, dört eşit özet kartı, solda kategori ilerlemesi ve son çalışmalar, sağda öğrenme alanları/çalışma bağlantıları/Premium tekrar araçları. Mobilde özetler iki sütun, içerik panelleri tek sütun olur; 390px taşma kontrolü başarılı.
- Mevcut sayaçlar, sınav tarihi, aktif konu testine devam, kayıtlı sorular, öğrenme ve Premium işlem rotaları korunur. DashboardController mevcut kullanıcıya ait tamamlanmış çalışmaların son üçünü ilişkileriyle görünüme verir; başka üyelerin veya bitmemiş oturumların sonuçları gösterilmez. StudentStatisticsTest bu sınırı doğrular.
- Önceki görünüm/kontrolcü ve test çıktısı C:/xampp/ehliyetgo-backups/dashboard-layout-20261113 altında. Canlı veri değiştirilmedi. Derleme başarılı; masaüstü ve mobil sayfa gözle kontrol edildi.
- Tam test paketi: 390 başarılı, 6479 assertion; ortam gerektiren 3 MySQL kurulum testi atlandı. Test süresi 57.89 saniye.

## 13 Kasım 2026 — Genel Bakış kartlarında ayrı alt bilgi şeridi
- Kullanıcının onayladığı Konu Anlatımı kart düzeni Genel Bakış özet/kategori/öğrenme/çalışma/son sonuç kartlarına uygulandı: üstte ikon ve başlık, altta açık renkli ayrı şeritte sayı, açıklama veya ilerleme. İç içe dış panel kutuları kaldırıldı. Yalnız dashboard Blade görünümü değişti; veri ve işlem akışları korunur.
- Masaüstü ve 390px mobil yerleşim kontrol edildi; yatay taşma yok. Önceki görünüm ve test çıktısı C:/xampp/ehliyetgo-backups/dashboard-card-footers-20261113 altında. Derleme başarılı.
- Tam test paketi: 390 başarılı, 6479 assertion; ortam gerektiren 3 MySQL kurulum testi atlandı (67.82 saniye).

## 13 Kasım 2026 — Medya Yönetimi
- Site Yönetimi altında /admin/media eklendi. Menü ve Rol ve İzinler kaydı AdminAccess içindedir; yeni media izni Editör için varsayılan kapalıdır. Livewire mount/render/silme işlemleri izin denetimini yeniden yapar.
- MediaLibrary public depolamadaki mevcut görselleri listeler; yeni tablo veya dosyaları yeniden yükleme gerekmez. Geçici Livewire dosyaları, görsel olmayan dosyalar, disk dışı yollar ve sembolik bağlantılar kapsam dışıdır. Kodla gelen public/images varlıkları bu yükleme kitaplığına dahil değildir.
- Kullanım indeksi taslak/pasif dahil soru metni/şıkları/açıklaması, blog kapağı/içeriği, konu anlatımı, sayfalar, trafik işaretleri, logo/favicon, profil fotoğrafı, reklam ve diğer kayıtlı içerik alanlarını kontrol eder. Mutlak/eski alan adlı /storage URL, göreli yol, URL kodlaması ve JSON kaçışları desteklenir. İlgili kayıt bağlantısı yalnız kullanıcının o bölüm izni varsa gösterilir.
- Yeni görsel/içerik modülü eklerken MediaLibrary::SOURCES kaydını ve kullanım testlerini de güncelleyin. Harici resimler yerel dosya olarak listelenmez. Henüz kaydedilmemiş form HTML'si kullanım tespitine dahil değildir; yeni yüklenen dosyalar ilk bir saat panelden silinemez.
- Görsel/ilgili içerik arama, kullanılan/kullanılmayan filtreleri, 24 öğeli sayfalama, önizleme ve kullanım bağlantıları vardır. Kullanılmayan görsel silme iki aşamalı satır içi onaydır; modal kullanılmaz. Silme anında kullanım tekrar taranır, dosya yolu doğrulanır ve işlem geçmişine kaydedilir. Kullanılan dosyalar silinemez.
- Blog kapağı, profil fotoğrafı, logo/favicon ve trafik işareti değiştirme/kaldırma temizliği de ortak kullanım taramasını kullanır; başka içerikte kullanılan eski görsel korunur.
- MediaManagementTest kullanım tespiti, taslaklar, JSON/URL eşleştirmesi, benzer dosya adları, yeni yükleme koruması, onay sonrası yeni kullanım, güvenli silme, çapraz içerik koruması ve izin iptalini doğrular. Tam paket 395 başarılı / 6542 assertion; ayrı ortam gerektiren 3 MySQL kurulum testi atlandı (63.38 saniye). pnpm run build başarılı.
- Canlı mevcut görselin Soru #591 kullanım bağlantısı, filtre ve 390px mobil görünüm kontrol edildi. Canlı dosya silinmedi. Önceki dosyalar ve test çıktısı: C:/xampp/ehliyetgo-backups/media-management-20261113.

## 14 Kasım 2026 — Genel Bakış bölümlerinin belirginleştirilmesi
- Yalnız pages/student/dashboard görünümü düzenlendi. Devam etme alanı koyu mavi; çalışma özeti dört renkli alt bilgi şeridiyle ayrıldı. Öğrenme ilerlemesi, konu kategorileri ve son çalışmalar kendi başlıkları/ayraçları altında tam genişlikte ayrı satırlara taşındı. Çalışma bağlantıları ve Premium alanı alt bölümde yer alır.
- Kartlarda beyaz başlık, belirgin renkli alt bilgi, büyük sayı/yüzde ve bağlantı oku kullanılır. Dar ekranda özetler iki, içerik kartları tek sütundur. İşlevler, sayaçlar ve veritabanı değişmedi; eski sütunlara ait kullanılmayan CSS kaldırıldı.
- Önceki görünüm: C:/xampp/ehliyetgo-backups/dashboard-clarity-20261114/dashboard.blade.php. Masaüstü ve 390px mobil görünüm kontrol edildi, yatay taşma yok; derleme başarılı. Test çıktısı aynı yedek klasöründe tests.txt.
- Son doğrulama: 395 test başarılı, 6542 assertion; ayrı MySQL kurulum ortamı gerektiren 3 test atlandı (60.47 saniye). Öğrenme alanları başlığı korundu.

## 23 Kasım 2026 — ehliyettestleri için devir notu

- Yerel geliştirme klasörü `C:\xampp\htdocs\ehliyettestleri`. Mevcut site bu konumdan çalışır. Yönetim ve öğrenci ekranlarındaki marka adı Site Ayarları üzerinden yönetilir.
- Geliştirmeye başlamadan önce bu klasördeki `AGENTS.md` kurallarını ve değiştirilecek bölümün bu rehberdeki notlarını okuyun. Yerel kurulum mevcut MariaDB veritabanını (`ehliyetgo_local`) kullanır.
- Taşıma sırasında veritabanında 2 kullanıcı ve 503 soru doğrulandı. `public/storage` yeni klasördeki yükleme alanına bağlı. Ana sayfa, yönetici girişi ve bir soru görseli yeni konumdan açıldı.
- Son ürün çalışmaları Medya Yönetimi ve öğrenci Genel Bakış ekranı üzerindeydi. Önceki geliştirme ve tasarım kararları için bu rehberdeki tarihli bölümlere bakın.
- Taşıma doğrulaması: Ana test koşusunda 395 test geçti. Atlanan 3 MySQL kurulum testi de ayrı bir MariaDB sunucusunda InstallationTest ile doğrulandı. Bu sınıftaki 6 testin tamamı geçti. Vite derlemesi başarılı oldu.

## 25 Kasım 2026 — Ziyaretçi çalışma ilerlemesi

- Ziyaretçi konu anlatımlarını ve trafik işaretlerini okuyabilir. Tamamlama, öğrenme ve tekrar listesi üyelik gerektirir. Ziyaretçiye ilerlemesini kaydetmek için üyelik bağlantısı gösterilir.
- Deneme, konu testi ve animasyonlu testlerde ziyaretçinin cevapları, konumu ve sonucu açık sayfanın belleğinde tutulur. Yenileme veya sayfadan ayrılma çözümü sıfırlar. Bitirmede sunucu yayımdaki soruları ve erişimi tekrar denetler, sonucu kayıt oluşturmadan döndürür.
- Ziyaretçi ilerlemesi Laravel oturumuna veya tarayıcı depolamasına yazılmaz. Üyelerin kayıtlı testleri kullanıcı kimliğine göre korunur. Konu testine kaldığı yerden devam etme ve denemelerin süre denetimi üyelerde geçerlidir.

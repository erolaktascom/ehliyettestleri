<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Exam;
use App\Models\Lecture;
use App\Models\LectureCategory;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\TopicTest;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin & Test Users
        User::firstOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Örnek Yönetici',
                'password' => Hash::make(app()->environment('testing') ? 'example-test-password' : Str::random(40)),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'ogrenci@example.test'],
            [
                'name' => 'Örnek Öğrenci',
                'password' => Hash::make(app()->environment('testing') ? 'example-test-password' : Str::random(40)),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        // 2. Topic Categories
        $catTrafik = TopicCategory::create([
            'name' => 'Trafik ve Çevre',
            'slug' => 'trafik-ve-cevre',
            'description' => 'Trafik kuralları, geçiş üstünlükleri ve çevre bilgisi.',
            'icon' => 'traffic',
            'order' => 1,
        ]);

        $catIlkYardim = TopicCategory::create([
            'name' => 'İlk Yardım',
            'slug' => 'ilk-yardim',
            'description' => 'Olay yeri güvenliği ve temel ilk yardım bilgisi.',
            'icon' => 'first-aid',
            'order' => 2,
        ]);

        $catArac = TopicCategory::create([
            'name' => 'Araç Tekniği',
            'slug' => 'arac-teknigi',
            'description' => 'Araç göstergeleri, motor ve güvenli bakım kontrolleri.',
            'icon' => 'engine',
            'order' => 3,
        ]);

        $catAdap = TopicCategory::create([
            'name' => 'Trafik Adabı',
            'slug' => 'trafik-adabi',
            'description' => 'Empati, iletişim ve sorumlu sürücülük bilinci.',
            'icon' => 'smile',
            'order' => 4,
        ]);

        $catAnimasyon = TopicCategory::create([
            'name' => 'Animasyonlu Sorular',
            'slug' => 'animasyonlu-sorular',
            'description' => 'Hareketli trafik senaryoları ve video tabanlı yeni nesil sorular.',
            'icon' => 'video',
            'order' => 5,
        ]);

        // 3. Exams (Deneme Sınavları - 50 Soru, 45 Dakika, 70 Baraj)
        $exams = [];
        for ($i = 1; $i <= 10; $i++) {
            $exams[$i] = Exam::create([
                'title' => "Seviye {$i}",
                'slug' => "deneme-seviye-{$i}",
                'description' => "2026 MEB müfredatına tam uyumlu 50 soruluk genel hazırlık deneme sınavı.",
                'duration_minutes' => 45,
                'passing_score' => 70,
                'order' => $i,
                'is_published' => true,
            ]);
        }
        $exam1 = $exams[1];
        $exam2 = $exams[2];

        // 4. Topic Tests
        $testTrafik1 = TopicTest::create([
            'topic_category_id' => $catTrafik->id,
            'title' => 'Seviye 1',
            'slug' => 'trafik-ve-cevre-seviye-1',
            'description' => 'Trafik işaretleri ve geçiş öncelikleri testi.',
            'order' => 1,
        ]);

        $testIlkYardim1 = TopicTest::create([
            'topic_category_id' => $catIlkYardim->id,
            'title' => 'Seviye 1',
            'slug' => 'ilk-yardim-seviye-1',
            'description' => 'Olay yeri güvenliği ve hasta/yaralı değerlendirme soruları.',
            'order' => 1,
        ]);

        $testAnimasyon1 = TopicTest::create([
            'section' => 'animated',
            'topic_category_id' => $catAnimasyon->id,
            'title' => 'Seviye 1',
            'slug' => 'animasyonlu-sorular-seviye-1',
            'description' => 'Hareketli video senaryolarıyla hazırlanan özel deneme.',
            'order' => 1,
        ]);

        // 5. Questions for Exams and Tests
        $questionsData = [
            [
                'question_text' => 'Aksine bir işaret yoksa, eğimsiz iki yönlü dar yolda, otomobil ile iş makinesinin karşılaşması halinde, hangisi diğerine yol vermelidir?',
                'options' => [['key' => 'A', 'text' => 'İş makinesi otomobile'], ['key' => 'B', 'text' => 'Otomobil iş makinesine'], ['key' => 'C', 'text' => 'Şeridi daralmış olan diğerine'], ['key' => 'D', 'text' => 'Hızı az olan diğerine']], 'correct_option' => 'A',
                'explanation' => 'Daralan veya eğimsiz yollarda kural gereği manevra kabiliyeti daha düşük olan ağır vasıtalar ve iş makineleri, otomobillere yol vermekle yükümlüdür.',
                'topic_category_id' => $catTrafik->id,
            ],
            [
                'question_text' => 'Trafik kazalarında ölümlerin en önemli nedenlerinden biri aşağıdakilerden hangisidir?',
                'options' => [['key' => 'A', 'text' => 'Soluk yolunun tıkanması'], ['key' => 'B', 'text' => 'Bacak kemiğinin kırılması'], ['key' => 'C', 'text' => 'Göz yaralanmaları'], ['key' => 'D', 'text' => 'Kollarda sıyrıklar oluşması']], 'correct_option' => 'A',
                'explanation' => 'Bilinci kapalı yaralılarda dilin geriye kaçması veya kusmuk gibi yabancı maddeler nedeniyle hava yolunun tıkanması dakikalar içinde ölüme yol açabilir.',
                'topic_category_id' => $catIlkYardim->id,
            ],
            [
                'question_text' => 'Aracın gösterge panelinde bulunan yağ basıncı ikaz ışığı yanıyorsa sürücü ne yapmalıdır?',
                'options' => [['key' => 'A', 'text' => 'Hızını artırmalıdır'], ['key' => 'B', 'text' => 'Aracı hemen uygun bir yerde durdurup motoru stop etmelidir'], ['key' => 'C', 'text' => 'Farları yakıp yola devam etmelidir'], ['key' => 'D', 'text' => 'Klimayı çalıştırmalıdır']], 'correct_option' => 'B',
                'explanation' => 'Yağ basıncı ikaz ışığı motorun yağlanmadığını gösterir; sürüşe devam edilirse motor kısa sürede kilitlenir ve ağır hasar alır.',
                'topic_category_id' => $catArac->id,
            ],
            [
                'question_text' => 'Sürücünün trafikte diğer yol kullanıcılarına karşı saygılı ve sabırlı davranması hangi kavramla ifade edilir?',
                'options' => [['key' => 'A', 'text' => 'Trafik adabı'], ['key' => 'B', 'text' => 'Hız kuralları'], ['key' => 'C', 'text' => 'Trafik cezası'], ['key' => 'D', 'text' => 'Araç muayenesi']], 'correct_option' => 'A',
                'explanation' => 'Trafik adabı; kuralların ötesinde karşılıklı hoşgörü, saygı ve sorumluluk bilincini kapsar.',
                'topic_category_id' => $catAdap->id,
            ],
            [
                'question_text' => 'Kavşağa yaklaşan bir sürücü sarı ışığın yandığını gördüğünde ne yapmalıdır?',
                'options' => [['key' => 'A', 'text' => 'Hızlanarak hemen geçmelidir'], ['key' => 'B', 'text' => 'Eğer emniyetle durabilecek mesafedeyse durmalıdır'], ['key' => 'C', 'text' => 'Kornaya basıp diğer araçları uyarmalıdır'], ['key' => 'D', 'text' => 'Geri vitese takmalıdır']], 'correct_option' => 'B',
                'explanation' => 'Sarı ışık ikaz anlamındadır; yolun trafiğe kapanmak üzere olduğunu belirtir. Güvenli duruş yapılabiliyorsa durulmalıdır.',
                'topic_category_id' => $catTrafik->id,
            ],
            [
                'question_text' => 'Aşağıdakilerden hangisi yetişkin bir insanda normal nabız sayısı aralığıdır (dakikada)?',
                'options' => [['key' => 'A', 'text' => '30 - 40'], ['key' => 'B', 'text' => '60 - 100'], ['key' => 'C', 'text' => '120 - 150'], ['key' => 'D', 'text' => '150 - 180']], 'correct_option' => 'B',
                'explanation' => 'Sağlıklı bir yetişkinde dinlenme halindeki normal nabız sayısı dakikada 60 ile 100 atım arasındadır.',
                'topic_category_id' => $catIlkYardim->id,
            ],
            [
                'question_text' => 'Dört zamanlı bir benzinli motorda ateşleme hangi parçanın kıvılcımı ile gerçekleşir?',
                'options' => [['key' => 'A', 'text' => 'Buji'], ['key' => 'B', 'text' => 'Enjektör'], ['key' => 'C', 'text' => 'Karbüratör'], ['key' => 'D', 'text' => 'Distribütör']], 'correct_option' => 'A',
                'explanation' => 'Benzinli motorlarda sıkıştırılmış yakıt-hava karışımı buji kıvılcımı ile ateşlenir.',
                'topic_category_id' => $catArac->id,
            ],
            [
                'question_text' => 'Trafik ortamında öfke kontrolü sağlayamayan bir sürücünün aşağıdaki davranışlardan hangisini yapma olasılığı yüksektir?',
                'options' => [['key' => 'A', 'text' => 'Hız sınırlarına daha çok dikkat etme'], ['key' => 'B', 'text' => 'Trafik kurallarını ihlal etme ve tehlikeli sollama yapma'], ['key' => 'C', 'text' => 'Diğer sürücülere yol verme'], ['key' => 'D', 'text' => 'Takip mesafesini iki katına çıkarma']], 'correct_option' => 'B',
                'explanation' => 'Öfke ve sabırsızlık risk alma eğilimini artırır ve kural ihlallerine yol açar.',
                'topic_category_id' => $catAdap->id,
            ],
            [
                'question_text' => 'Trafik kazasında bilinci açık ancak omurga yaralanması şüphesi olan bir yaralı nasıl taşınmalıdır?',
                'options' => [['key' => 'A', 'text' => 'Kollarından tutularak sürüklenmeli'], ['key' => 'B', 'text' => 'Baş, boyun ve gövde ekseni korunarak sedye ile'], ['key' => 'C', 'text' => 'Sırtına bindirilerek'], ['key' => 'D', 'text' => 'Kucakta taşınarak']], 'correct_option' => 'B',
                'explanation' => 'Omurga yaralanmalarında yanlış hareket ettirme kalıcı felce veya ölüme neden olabilir; baş-boyun-gövde ekseni bozulmamalıdır.',
                'topic_category_id' => $catIlkYardim->id,
            ],
            [
                'question_text' => 'Aşağıdakilerden hangisi araçta yakıt tasarrufu sağlamaya yardımcı olur?',
                'options' => [['key' => 'A', 'text' => 'Ani hızlanma ve ani frenlerden kaçınmak'], ['key' => 'B', 'text' => 'Lastik hava basınçlarını düşük tutmak'], ['key' => 'C', 'text' => 'Aracı sürekli yüksek devirde kullanmak'], ['key' => 'D', 'text' => 'Camları sürekli sonuna kadar açık tutmak']], 'correct_option' => 'A',
                'explanation' => 'Sakin ve öngörülü sürüş tarzı yakıt tüketimini %20-30 oranında azaltır.',
                'topic_category_id' => $catArac->id,
            ],
            [
                'question_text' => 'Otoyolda seyrederken ters yöne giren bir sürücünün yapması gereken en doğru hareket nedir?',
                'options' => [['key' => 'A', 'text' => 'Geri geri gitmeye devam etmek'], ['key' => 'B', 'text' => 'Hemen sağ emniyet şeridine çekip durmak ve yardım çağırmak'], ['key' => 'C', 'text' => 'U dönüşü yaparak hızla devam etmek'], ['key' => 'D', 'text' => 'Dörtlüleri yakıp son hızla ilerlemek']], 'correct_option' => 'B',
                'explanation' => 'Ters yönde seyretmek hayati tehlikedir; derhal emniyetli alana sığınıp kolluk kuvvetlerine bilgi verilmelidir.',
                'topic_category_id' => $catTrafik->id,
            ],
            [
                'question_text' => 'Trafikte kırmızı ışıkta beklerken yeşil ışık yandığı anda önündeki sürücü henüz hareket etmeden korna çalan sürücünün davranışı neyi gösterir?',
                'options' => [['key' => 'A', 'text' => 'Sabırsızlık ve tahammülsüzlük'], ['key' => 'B', 'text' => 'İleri düzey sürüş kabiliyeti'], ['key' => 'C', 'text' => 'Trafik güvenliğini artırma isteği'], ['key' => 'D', 'text' => 'Yakıt tasarrufu']], 'correct_option' => 'A',
                'explanation' => 'Hemen korna çalmak trafik adabına aykırı sabırsızlık örneğidir.',
                'topic_category_id' => $catAdap->id,
            ],
        ];

        // Attach questions to exams and topic tests
        $order = 1;
        foreach ($questionsData as $qData) {
            $question = Question::create($qData);
            $exam1->questions()->attach($question->id, ['position' => $order++]);
        }

        // Mini exam questions
        for ($i = 0; $i < 4; $i++) {
            $question = Question::create($questionsData[$i]);
            $exam2->questions()->attach($question->id, ['position' => $i + 1]);
        }

        // Questions for exams 3 through 10
        for ($e = 3; $e <= 10; $e++) {
            $question = Question::create($questionsData[0]);
            $exams[$e]->questions()->attach($question->id, ['position' => 1]);
        }

        // Animated Questions
        $animQuestions = [
            [
                'question_text' => 'Animasyonlu Senaryo: Videodaki kavşakta 1 numaralı araç dönel kavşak içindedir, 2 numaralı araç ise kavşağa yaklaşmaktadır. İlk geçiş hakkı kime aittir?',
                'options' => [['key' => 'A', 'text' => '1 numaralı araca (Dönel kavşak içindeki araç)'], ['key' => 'B', 'text' => '2 numaralı araca (Kavşağa yaklaşan araç)'], ['key' => 'C', 'text' => 'Hızlı olana'], ['key' => 'D', 'text' => 'Büyük olan araca']], 'correct_option' => 'A',
                'explanation' => 'Trafik kurallarına göre dönel kavşak içindeki araçların geçiş üstünlüğü bulunmaktadır.',
                'topic_category_id' => $catAnimasyon->id,
            ],
            [
                'question_text' => 'Animasyonlu Senaryo: Yağmurlu havada ani fren yapan öndeki aracı takip eden sürücünün kazadan kaçınması için en kritik kural nedir?',
                'options' => [['key' => 'A', 'text' => 'Takip mesafesini artırmış olması'], ['key' => 'B', 'text' => 'Kornaya basması'], ['key' => 'C', 'text' => 'Sürekli sol şeridi işgal etmesi'], ['key' => 'D', 'text' => 'Far ayarlarını yükseltmesi']], 'correct_option' => 'A',
                'explanation' => 'Islak zeminde fren mesafesi 2 katına kadar uzayabilir. Takip mesafesini korumak hayati öneme sahiptir.',
                'topic_category_id' => $catAnimasyon->id,
            ],
            [
                'question_text' => 'Animasyonlu Senaryo: Yaya geçidine yaklaşırken duraklayan otobüsün önünden yola çıkan yayayı gören araç sürücüsü ne yapmalıdır?',
                'options' => [['key' => 'A', 'text' => 'Kornaya basıp hız kesmeden geçmelidir'], ['key' => 'B', 'text' => 'Hemen hızını düşürüp yayaya ilk geçiş hakkını vermelidir'], ['key' => 'C', 'text' => 'Otobüsün solundan hızla sollamalıdır'], ['key' => 'D', 'text' => 'Yayaya el işareti yapmalıdır']], 'correct_option' => 'B',
                'explanation' => 'Yaya geçitlerinde ve toplu taşıma araçlarının yanından geçerken öncelik daima yayalara aittir.',
                'topic_category_id' => $catAnimasyon->id,
            ],
        ];

        foreach ($animQuestions as $aq) {
            $question = Question::create($aq);
            $testAnimasyon1->questions()->attach($question->id, ['position' => $testAnimasyon1->questions()->count() + 1]);
        }

        // 6. Traffic Sign Categories & Signs
        $signCat1 = TrafficSignCategory::create([
            'name' => 'Tehlike Uyarı İşaretleri',
            'slug' => 'tehlike-uyari-isaretleri',
            'description' => 'Yol üzerindeki tehlikelere karşı sürücüyü önceden uyaran üçgen biçimli levhalar.',
            'order' => 1,
        ]);

        $signCat2 = TrafficSignCategory::create([
            'name' => 'Trafik Tanzim İşaretleri',
            'slug' => 'trafik-tanzim-isaretleri',
            'description' => 'Yasaklama, sınırlama ve zorunluluk bildiren düzenleyici yuvarlak levhalar.',
            'order' => 2,
        ]);

        $signCat3 = TrafficSignCategory::create([
            'name' => 'Bilgi İşaretleri',
            'slug' => 'bilgi-isaretleri',
            'description' => 'Yol, hizmet ve kullanım alanları hakkında bilgi veren kare ve dikdörtgen levhalar.',
            'order' => 3,
        ]);

        $signs = [
            [
                'traffic_sign_category_id' => $signCat1->id,
                'code' => 'T-1',
                'name' => 'Dikkat',
                'meaning' => 'Yol üzerinde ayrıca belirtilmeyen bir tehlikeye karşı dikkatli olunması gerektiğini bildirir.',
                'order' => 1,
            ],
            [
                'traffic_sign_category_id' => $signCat1->id,
                'code' => 'T-2',
                'name' => 'Yol Çalışması',
                'meaning' => 'İleride yol yapım, bakım veya onarım çalışması bulunduğunu bildirir; hız azaltılır ve görevlilerin işaretlerine uyulur.',
                'order' => 2,
            ],
            [
                'traffic_sign_category_id' => $signCat1->id,
                'code' => 'T-3',
                'name' => 'Işıklı İşaret Cihazı',
                'meaning' => 'İleride trafik ışıkları bulunduğunu bildirir; sürücü durmaya hazır şekilde yaklaşır.',
                'order' => 3,
            ],
            [
                'traffic_sign_category_id' => $signCat1->id,
                'code' => 'T-4',
                'name' => 'Yaya Geçidi',
                'meaning' => 'İleride yaya geçidi bulunduğunu ve hızın azaltılması gerektiğini bildirir.',
                'order' => 4,
            ],
            [
                'traffic_sign_category_id' => $signCat1->id,
                'code' => 'T-5',
                'name' => 'Kaygan Yol',
                'meaning' => 'Yol yüzeyinin kaygan olabileceğini ve ani direksiyon/fren hareketlerinden kaçınılması gerektiğini bildirir.',
                'order' => 5,
            ],
            [
                'traffic_sign_category_id' => $signCat1->id,
                'code' => 'T-6',
                'name' => 'Kontrolsüz Kavşak',
                'meaning' => 'İleride trafik ışığı veya görevli bulunmayan kavşak olduğunu bildirir; hız azaltılıp geçiş kurallarına uyulur.',
                'order' => 6,
            ],
            [
                'traffic_sign_category_id' => $signCat1->id,
                'code' => 'T-7',
                'name' => 'Gevşek Malzemeli Zemin',
                'meaning' => 'Yol üzerinde taş veya gevşek malzeme bulunabileceğini, takip mesafesinin artırılması gerektiğini bildirir.',
                'order' => 7,
            ],
            // Tanzim
            [
                'traffic_sign_category_id' => $signCat2->id,
                'code' => 'TT-1',
                'name' => 'Girişi Olmayan Yol',
                'meaning' => 'Trafiğe ters yönden girilmesinin yasak olduğunu bildirir.',
                'order' => 1,
            ],
            [
                'traffic_sign_category_id' => $signCat2->id,
                'code' => 'TT-2',
                'name' => 'Hız Sınırı (50)',
                'meaning' => 'Maksimum hız sınırının saatte 50 kilometre olduğunu bildirir.',
                'order' => 2,
            ],
            [
                'traffic_sign_category_id' => $signCat2->id,
                'code' => 'TT-3',
                'name' => 'Öndeki Taşıtı Geçmek Yasaktır',
                'meaning' => 'Bu levhadan sonra öndeki taşıtın sollanması yasaktır.',
                'order' => 3,
            ],
            [
                'traffic_sign_category_id' => $signCat2->id,
                'code' => 'TT-4',
                'name' => 'Park Etmek Yasaktır',
                'meaning' => 'Araçların bu alana park edilmesinin yasak olduğunu belirtir.',
                'order' => 4,
            ],
            // Bilgi
            [
                'traffic_sign_category_id' => $signCat3->id,
                'code' => 'B-1',
                'name' => 'Hastane',
                'meaning' => 'Yakında hastane bulunduğunu ve gereksiz korna çalınmaması gerektiğini bildirir.',
                'order' => 1,
            ],
            [
                'traffic_sign_category_id' => $signCat3->id,
                'code' => 'B-2',
                'name' => 'Akaryakıt İstasyonu',
                'meaning' => 'İleride benzin ve motorin alabileceğiniz akaryakıt istasyonu olduğunu belirtir.',
                'order' => 2,
            ],
            [
                'traffic_sign_category_id' => $signCat3->id,
                'code' => 'B-3',
                'name' => 'Tek Yön',
                'meaning' => 'Yolun tek yönlü trafik akışına sahip olduğunu gösterir.',
                'order' => 3,
            ],
            [
                'traffic_sign_category_id' => $signCat3->id,
                'code' => 'B-4',
                'name' => 'Otoyol Başlangıcı',
                'meaning' => 'Otoyol kurallarının başladığını ve asgari hız sınırlarına uyulması gerektiğini bildirir.',
                'order' => 4,
            ],
        ];

        foreach ($signs as $s) {
            TrafficSign::create($s);
        }

        // 7. Lecture Categories and Lectures
        $lecCat1 = LectureCategory::create([
            'name' => 'İlk Yardım Bilgisi',
            'slug' => 'ilk-yardim-bilgisi',
            'description' => 'Kaza yerinde doğru değerlendirme ve temel ilk yardım uygulamaları.',
            'order' => 1,
        ]);

        $lecCat2 = LectureCategory::create([
            'name' => 'Araç Tekniği ve Motor',
            'slug' => 'arac-teknigi-ve-motor',
            'description' => 'Aracın temel sistemlerini, göstergelerini ve güvenli bakım kontrollerini tanıyın.',
            'order' => 2,
        ]);

        $lecCat3 = LectureCategory::create([
            'name' => 'Trafik Adabı',
            'slug' => 'trafik-adabi',
            'description' => 'Trafikte empati, sabır, sorumluluk ve etkili iletişim davranışları.',
            'order' => 3,
        ]);

        Lecture::create([
            'lecture_category_id' => $lecCat1->id,
            'title' => 'Olay Yeri Değerlendirmesi',
            'slug' => 'olay-yeri-degerlendirmesi',
            'summary' => 'İlk yardım öncesinde çevre, kendi güvenliğiniz ve yaralının durumunu değerlendirme sırası.',
            'content' => '<h2>Önce güvenlik</h2><p>İlk yardımcı kendisini, yaralıyı ve çevredeki kişileri yeni bir tehlikeye sokmadan hareket etmelidir. Trafik güvenliği sağlanır, gerekiyorsa motor durdurulur ve çevrede ateş kullanılmaz.</p><h2>Bildirme ve değerlendirme</h2><ol><li>112 Acil Çağrı Merkezi aranır.</li><li>Konum, olayın türü ve yaralı sayısı açıkça bildirilir.</li><li>Yaralının bilinci ve solunumu değerlendirilir.</li></ol><p>Telefon, görevli görüşmeyi sonlandırmadan kapatılmaz.</p>',
            'order' => 1,
        ]);

        Lecture::create([
            'lecture_category_id' => $lecCat1->id,
            'title' => 'Temel Yaşam Desteğinin Temelleri',
            'slug' => 'temel-yasam-destegi',
            'summary' => 'Solunum ve dolaşımı duran kişiye uygulanan göğüs basısı ve suni solunum adımları.',
            'content' => '<h2>Hava yolu açıklığı</h2><p>Baş-çene pozisyonu verilerek dilin soluk borusunu tıkaması önlenir. Ağız içi kontrol edilerek yabancı cisim varsa çıkarılır.</p><h2>Kalp masajı ve suni solunum</h2><p>30 göğüs basısı ve 2 suni solunum şeklinde uygulanır. Dakikada 100-120 bası ritmi korunmalıdır.</p>',
            'order' => 2,
        ]);

        Lecture::create([
            'lecture_category_id' => $lecCat2->id,
            'title' => 'Motor Yağı ve Soğutma Sıvısı Kontrolleri',
            'slug' => 'motor-yagi-ve-sogutma',
            'summary' => 'Motorun ömrünü uzatan periyodik kontroller ve yağ çubuğu okuma teknikleri.',
            'content' => '<h2>Motor yağ seviyesi</h2><p>Araç düz zeminde ve motor durduktan 5-10 dakika sonra yağ çubuğu çekilip silinir, tekrar sokulur. Yağ seviyesi iki çizgi arasında olmalıdır.</p><h2>Radyatör soğutma sıvısı</h2><p>Motor sıcakken asla radyatör kapağı açılmaz. Antifriz kışın donmayı, yazın harareti engeller.</p>',
            'order' => 1,
        ]);

        Lecture::create([
            'lecture_category_id' => $lecCat3->id,
            'title' => 'Trafikte Empati ve Öfke Kontrolü',
            'slug' => 'empati-ve-ofke-kontrolu',
            'summary' => 'Trafik ortamında duygusal dengeyi korumanın sürüş güvenliğine etkileri.',
            'content' => '<h2>Empati kurmak</h2><p>Trafikteki diğer sürücülerin ve yayaların da hata yapabileceğini kabul etmek ve öç alma güdüsünden uzak durmak gerekir.</p><h2>Öfkeyle baş etme</h2><p>Derin nefes almak, müzik dinlemek ve yolculuğa zamanında çıkmak stresi belirgin şekilde azaltır.</p>',
            'order' => 1,
        ]);

        // 8. Blog Categories & Posts
        $blogCat1 = BlogCategory::create([
            'name' => 'Sınav Rehberi',
            'slug' => 'sinav-rehberi',
        ]);

        $blogCat2 = BlogCategory::create([
            'name' => 'Güvenli Sürüş',
            'slug' => 'guvenli-surus',
        ]);

        BlogPost::create([
            'blog_category_id' => $blogCat2->id,
            'title' => 'Güvenli Sürüşte Takip Mesafesi Neden Önemlidir?',
            'slug' => 'guvenli-suruste-takip-mesafesi',
            'summary' => 'Takip mesafesini yol, hava ve araç koşullarına göre ayarlamanın güvenliğe etkisi.',
            'content' => '<h2>Tepki için alan bırakır</h2><p>Öndeki aracın ani freninde sürücünün tehlikeyi algılaması, tepki vermesi ve aracın durması zaman alır. Yeterli mesafe bu sürecin güvenli tamamlanmasını sağlar.</p><h2>Her koşul aynı değildir</h2><p>Yağış, buzlanma, düşük görüş, ağır yük ve yorgunluk gibi etkenlerde mesafe artırılmalıdır. Güvenli sürüş yalnız yasal sınıra değil, mevcut koşula uygun hız ve mesafeye dayanır.</p><h2>Yakın takipten kaçının</h2><p>Yakın takip görüş alanını daraltır, ani fren ihtimalini artırır ve diğer sürücülerde baskı oluşturur.</p>',
            'author_name' => 'Mehmet AKTAŞ',
            'views_count' => 142,
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        BlogPost::create([
            'blog_category_id' => $blogCat1->id,
            'title' => 'Ehliyet Sınavına Hazırlık Planı Nasıl Oluşturulur?',
            'slug' => 'ehliyet-sinavina-hazirlik-plani',
            'summary' => 'Konu çalışması, açıklamalı test ve süreli denemeyi dengeli kullanan uygulanabilir bir hazırlık planı.',
            'content' => '<h2>1. Adım: Konu eksiklerini kapatın</h2><p>İlk Yardım, Trafik ve Motor konularındaki temel terimleri ve kuralları öğrenin.</p><h2>2. Adım: Bol deneme çözün</h2><p>Gerçek sınav formatında 45 dakikalık deneme sınavları çözerek sınav stresini azaltın.</p>',
            'author_name' => 'Mehmet AKTAŞ',
            'views_count' => 98,
            'is_published' => true,
            'published_at' => now()->subDays(5),
        ]);

        BlogPost::create([
            'blog_category_id' => $blogCat1->id,
            'title' => 'Ehliyet Sınavına Hazırlanırken 5 Öneri',
            'slug' => 'ehliyet-sinavina-hazirlanirken-5-oneri',
            'summary' => 'Çalışma sürenizi daha verimli kullanmak ve eksiklerinizi kapatmak için uygulanabilir öneriler.',
            'content' => '<h2>Düzenli tekrar başarının anahtarıdır</h2><p>Günde en az 1 deneme çözerek yanlış yaptığınız soruların çözümlerini dikkatle okuyun.</p>',
            'author_name' => 'Mehmet AKTAŞ',
            'views_count' => 210,
            'is_published' => true,
            'published_at' => now()->subDays(7),
        ]);

        // 9. Static Pages
        \App\Models\Page::firstOrCreate(
            ['slug' => 'hakkimizda'],
            [
                'title' => 'Hakkımızda',
                'short_description' => '%site_adi% vizyon ve misyonu',
                'content' => '<p>%site_adi%, sürücü adaylarına modern dijital hazırlık imkanı sunar.</p>',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        \App\Models\Page::firstOrCreate(
            ['slug' => 'gizlilik-politikasi'],
            [
                'title' => 'Gizlilik Politikası',
                'short_description' => 'Kullanıcı verilerinin korunması ve gizlilik taahhüdümüz.',
                'content' => '<p>%site_adi% olarak kullanıcılarımızın kişisel verilerinin korunmasına büyük önem veriyoruz. Sistemimizde toplanan veriler yalnızca sınav deneyimini geliştirmek amacıyla kullanılır.</p>',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        \App\Models\Page::firstOrCreate(
            ['slug' => 'kullanim-kosullari'],
            [
                'title' => 'Kullanım Koşulları',
                'short_description' => 'Platform kullanım şartları ve telif hakları.',
                'content' => '<p>%site_adi% platformunu kullanan tüm üyelerimiz kullanım şartlarını kabul etmiş sayılır. Test ve sınav içerikleri eğitim amaçlıdır.</p>',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        // 10. Site Settings & SEO
        \App\Models\SiteSetting::firstOrCreate(
            ['id' => 1],
            [
                'site_name' => 'EhliyetGo',
                'phone' => null,
                'address' => 'Levent, Büyükdere Cad. No:199, İstanbul',
            ]
        );

        \App\Models\SeoSetting::firstOrCreate(
            ['id' => 1],
            [
                'robots_txt' => "User-agent: *\nAllow: /",
                'sitemap_enabled' => true,
            ]
        );
    }
}

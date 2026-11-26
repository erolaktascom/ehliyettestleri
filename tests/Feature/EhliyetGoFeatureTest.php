<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EhliyetGoFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_homepage_loads_correctly(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Ehliyet sınavına');
        $response->assertSee('2026 MEB Müfredatına Tam Uyumlu');
    }

    public function test_exams_list_loads(): void
    {
        $response = $this->get('/deneme');
        $response->assertStatus(200);
        $response->assertSee('Seviye 1');
        $response->assertSee('Seviye 2');
        // Check that question count and minutes are not rendered in listing items
        $response->assertDontSee('50 Soru</span>', false);
        $response->assertDontSee('45 Dakika</span>', false);
        // Misafir modu banner must be removed as requested
        $response->assertDontSee('Misafir Modu:</strong> Denemeleri çözebilirsiniz', false);
        // Guest sees start icon action for first exams
        $response->assertSee('title="Sınavı Başlat"', false);
        // Guest sees locked status and Üyelere Özel for later exams
        $response->assertSee('Üyelere Özel');
        $response->assertSee('Kilitli');
        $response->assertSee('Üye Olun');
    }

    public function test_guest_is_redirected_when_attempting_level_4_exam(): void
    {
        $exam4 = Exam::where('order', 4)->first();
        if ($exam4) {
            $response = $this->post('/deneme/' . $exam4->slug . '/baslat');
            $response->assertRedirect(route('register'));
        }
    }

    public function test_authenticated_user_sees_status_icons_in_exam_list(): void
    {
        $user = User::where('email', 'ogrenci@example.test')->first();
        $exam = Exam::first();

        // 1. Initially not started
        $response = $this->actingAs($user)->get('/deneme');
        $response->assertStatus(200);
        $response->assertSee('Başlanmadı');

        // 2. Deneme sınavlarında yarım bırakmak yok
        $attempt = ExamAttempt::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'session_id' => 'test-session',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(45),
            'total_questions' => 50,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->get('/deneme');
        $response->assertStatus(200);
        // Deneme sınavında yarım kaldı ve kaldığın yerden devam et yer almaz
        $response->assertDontSee('Yarım Kaldı');
        $response->assertDontSee('Kaldığın Yerden Devam Et');

        // 3. Complete the attempt -> completed
        $attempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score' => 84,
            'is_passed' => true,
        ]);

        $response = $this->actingAs($user)->get('/deneme');
        $response->assertStatus(200);
        $response->assertSee('Geçti · Tamamlandı')->assertSee('<strong>84</strong> Puan', false);
        $response->assertSee('Sonucu ve Çözümleri Gör');

        // 4. Konu testlerinde ise yarım bırakmak var
        $category = \App\Models\TopicCategory::where('slug', 'trafik-ve-cevre')->first();
        if ($category && $category->topicTests()->exists()) {
            $topicTest = $category->topicTests()->first();
            ExamAttempt::create([
                'user_id' => $user->id,
                'topic_test_id' => $topicTest->id,
                'session_id' => 'test-topic-session',
                'started_at' => now(),
                'expires_at' => now()->addMinutes(45),
                'total_questions' => 20,
                'status' => 'in_progress',
            ]);

            $topicResponse = $this->actingAs($user)->get('/konu-testleri/' . $category->slug);
            $topicResponse->assertStatus(200);
            $topicResponse->assertSee('Yarım Kalan');
            $topicResponse->assertSee('Kaldığın Yerden Devam Et');
        }
    }

    public function test_exam_start_and_ajax_answering(): void
    {
        $exam = Exam::first();
        $response = $this->actingAs(User::factory()->create())->post('/deneme/' . $exam->slug . '/baslat');
        $response->assertRedirect();

        $attempt = ExamAttempt::sole();
        $this->assertEquals('in_progress', $attempt->status);

        // Take view
        $this->get('/sinav/oturum/' . $attempt->id)->assertRedirect($attempt->playUrl());
        $takeResponse = $this->get($attempt->playUrl());
        $takeResponse->assertStatus(200);
        $takeResponse->assertViewHas('remainingSeconds', function ($seconds) {
            return $seconds >= 2690 && $seconds <= 2700;
        });

        // Save Answer via AJAX
        $question = $exam->questions->first();
        $ajaxResponse = $this->postJson('/sinav/oturum/' . $attempt->id . '/cevap', [
            'question_id' => $question->id,
            'selected_option' => 'A',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['status' => 'success']);

        // Finish Exam
        $finishResponse = $this->post('/sinav/oturum/' . $attempt->id . '/bitir');
        $finishResponse->assertRedirect(route('exams.result', $attempt));

        $attempt = $attempt->fresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertDatabaseCount('exam_attempts', 1);
        $this->assertDatabaseCount('exam_attempt_answers', 1);

        // Results view
        $resultResponse = $this->get('/sinav/oturum/' . $attempt->id . '/sonuc');
        $resultResponse->assertStatus(200);
        $resultResponse->assertSee('Puan');
    }

    public function test_traffic_signs_module(): void
    {
        $response = $this->get('/trafik-isaretleri');
        $response->assertStatus(200);

        $catResponse = $this->get('/ogren/trafik-isaretleri');
        $catResponse->assertStatus(301)->assertRedirect('/trafik-isaretleri');

        $cat = TrafficSignCategory::first();
        $learnResponse = $this->get('/ogren/trafik-isaretleri/' . $cat->slug);
        $learnResponse->assertStatus(200);

        $sign = TrafficSign::first();
        $reviewResponse = $this->postJson('/trafik-isaretleri/' . $sign->id . '/incele', [
            'status' => 'known',
        ]);
        $reviewResponse->assertUnauthorized();
    }

    public function test_admin_user_can_authenticate(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'example-test-password',
        ]);
        $response->assertRedirect('/admin');
    }

    public function test_wrong_questions_practice_flow_and_retake_integrity(): void
    {
        $user = User::factory()->create(['premium_until' => now()->addMonth()]);
        $exam = Exam::first();
        $question = $exam->questions->first();

        // 1. User attempts exam and gets the question wrong
        $attempt = ExamAttempt::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(25),
            'completed_at' => now(),
            'total_questions' => 1,
            'score' => 0,
            'is_passed' => false,
        ]);

        \App\Models\ExamAttemptAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option' => 'D', // wrong answer
            'is_correct' => false,
        ]);

        // 2. Dashboard should show 1 unresolved wrong question
        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertViewHas('unresolvedWrongCount', 1)->assertSee('1 yanlış soruyu tekrar çöz');

        // 3. Start practice session
        $startPracticeResponse = $this->actingAs($user)->post(route('student.wrong-questions.start'));
        $startPracticeResponse->assertRedirect();

        $practiceAttempt = ExamAttempt::where('user_id', $user->id)
            ->whereHas('exam', fn($q) => $q->where('slug', 'yanlis-sorular-tekrar-testi'))
            ->latest()
            ->first();

        $this->assertNotNull($practiceAttempt);

        // 4. In practice session, student answers correctly
        $this->actingAs($user)->postJson('/sinav/oturum/' . $practiceAttempt->id . '/cevap', [
            'question_id' => $question->id,
            'selected_option' => $question->correct_option,
        ]);

        $this->actingAs($user)->post('/sinav/oturum/' . $practiceAttempt->id . '/bitir');

        // 5. Dashboard should now show 0 unresolved wrong questions (cleared)
        $dashboardResponse2 = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse2->assertStatus(200);
        $dashboardResponse2->assertViewHas('unresolvedWrongCount', 0)->assertDontSee('yanlış soruyu tekrar çöz');
        $dashboardResponse2->assertSee('Performans raporunu aç');

        // 6. Original exam score should remain 0 (not artificially inflated)
        $attempt->refresh();
        $this->assertEquals(0, $attempt->score);

        // 7. Retaking the official exam starts a new attempt
        $retakeResponse = $this->actingAs($user)->post(route('exams.start', $exam));
        $retakeResponse->assertRedirect();

        $newAttempt = ExamAttempt::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->first();
        $this->assertNotNull($newAttempt);
    }

    public function test_dynamic_pages_render_and_footer_links_work(): void
    {
        $privacyResponse = $this->get('/gizlilik-politikasi');
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertSee('Gizlilik');

        $termsResponse = $this->get('/kullanim-kosullari');
        $termsResponse->assertStatus(200);
        $termsResponse->assertSee('Kullanım');

        $home = $this->get('/');
        $home->assertSee('/gizlilik-politikasi');
        $home->assertSee('/kullanim-kosullari');
    }

    public function test_topic_tests_frontend_list_renders(): void
    {
        $response = $this->get('/konu-testleri');
        $response->assertStatus(200);
        $response->assertSee('Konu Testleri');
    }

    public function test_lecture_topics_frontend_renders(): void
    {
        $response = $this->get('/konu');
        $response->assertStatus(200);
        $response->assertSee('Konu Anlatımı');
    }
}

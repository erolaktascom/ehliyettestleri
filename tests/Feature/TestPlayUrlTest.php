<?php

namespace Tests\Feature;

use App\Models\{Exam, ExamAttempt, Question, SeoRedirect, TopicCategory, TopicTest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestPlayUrlTest extends TestCase
{
    use RefreshDatabase;

    private function exam(): Exam
    {
        $exam = Exam::create(['title' => 'Seviye 1', 'slug' => 'seviye-1', 'is_published' => true, 'order' => 1, 'duration_minutes' => 45]);
        $exam->questions()->create(['question_text' => 'Korunan soru', 'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']], 'correct_option' => 'A']);
        return $exam;
    }

    private function topicTest(): TopicTest
    {
        $category = TopicCategory::create(['name' => 'Trafik', 'slug' => 'trafik-ve-cevre', 'is_active' => true]);
        $test = TopicTest::create(['title' => 'Seviye 1', 'slug' => 'seviye-1', 'topic_category_id' => $category->id, 'is_published' => true]);
        $test->questions()->create(['question_text' => 'Konu sorusu', 'topic_category_id' => $category->id, 'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']], 'correct_option' => 'A']);
        return $test;
    }

    public function test_exam_slug_resumes_own_attempt_without_resetting_answers_or_time(): void
    {
        $exam = $this->exam();
        $this->actingAs(User::factory()->create());
        $this->assertSame(url('/deneme/seviye-1'), route('exams.start', $exam));
        $this->get('/deneme/seviye-1/baslat')->assertRedirect('/deneme/seviye-1');
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->post(route('exams.start', $exam))->assertRedirect('/deneme/seviye-1');
        $attempt = ExamAttempt::sole();
        $this->postJson(route('exams.save-answer', $attempt), ['question_id' => $exam->questions->first()->id, 'selected_option' => 'B'])->assertOk();
        $before = $attempt->getAttributes();
        foreach (range(1, 2) as $visit) {
            $this->get('/deneme/seviye-1')->assertOk()->assertViewHas('attempt', fn ($record) => $record->id === $attempt->id)
                ->assertViewHas('answers', fn ($answers) => $answers->first()->selected_option === 'B')
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
        $this->assertSame($before, $attempt->fresh()->getAttributes());
        $this->assertDatabaseCount('exam_attempts', 1);
        $this->get(route('exams.take', $attempt))->assertRedirect('/deneme/seviye-1');
        $this->get(route('exams.result', $attempt))->assertRedirect('/deneme/seviye-1');
    }

    public function test_same_url_is_private_to_each_user_and_guests_do_not_get_member_answers(): void
    {
        $exam = $this->exam();
        $first = User::factory()->create(); $second = User::factory()->create();
        $this->actingAs($first)->post(route('exams.start', $exam));
        $one = ExamAttempt::latest('id')->first();
        $this->actingAs($second)->get('/deneme/seviye-1')->assertRedirect('/deneme/seviye-1');
        $this->assertDatabaseCount('exam_attempts', 2);
        $two = ExamAttempt::latest('id')->first();
        $this->get('/deneme/seviye-1')->assertViewHas('attempt', fn ($record) => $record->id === $two->id);
        $this->get(route('exams.take', $one))->assertForbidden();
        $this->actingAs($first)->get('/deneme/seviye-1')->assertViewHas('attempt', fn ($record) => $record->id === $one->id);
        auth()->logout();
        $this->get('/deneme/seviye-1')->assertOk()->assertViewHas('gradeUrl', route('guest.exam-result', $exam));
        $this->get(route('exams.take', $one))->assertForbidden();
    }

    public function test_topic_slug_is_scoped_to_category_and_legacy_url_redirects(): void
    {
        $test = $this->topicTest();
        $this->actingAs(User::factory()->create())->post(route('topic-tests.start', $test))
            ->assertRedirect('/konu-testleri/trafik-ve-cevre/seviye-1');
        $attempt = ExamAttempt::sole();
        $this->get($attempt->playUrl())->assertOk()->assertViewHas('remainingSeconds', 0);
        $this->get(route('topic-tests.take', $attempt))->assertRedirect($attempt->playUrl());
        TopicCategory::create(['name' => 'Başka', 'slug' => 'baska', 'is_active' => true]);
        $this->get('/konu-testleri/baska/seviye-1')->assertNotFound();
        $this->get('/konu-testleri/trafik-ve-cevre')->assertOk();
    }

    public function test_direct_links_start_once_and_drafts_and_member_only_exams_stay_protected(): void
    {
        $exam = $this->exam(); $test = $this->topicTest();
        $this->actingAs(User::factory()->create());
        foreach (['/deneme/seviye-1', '/konu-testleri/trafik-ve-cevre/seviye-1'] as $url) {
            $this->get($url)->assertRedirect($url)->assertHeader('Cache-Control', 'no-store, private');
            $attempt = ExamAttempt::latest('id')->first();
            $before = $attempt->getAttributes();
            $this->get($url)->assertOk()->assertViewHas('attempt', fn ($row) => $row->id === $attempt->id);
            $this->assertSame($before, $attempt->fresh()->getAttributes());
        }
        $this->assertDatabaseCount('exam_attempts', 2);
        $this->actingAs(User::factory()->create());
        $exam->update(['is_published' => false]); $test->update(['is_published' => false]);
        $this->get('/deneme/seviye-1')->assertNotFound();
        $this->get('/konu-testleri/trafik-ve-cevre/seviye-1')->assertNotFound();
        auth()->logout();
        $exam->update(['is_published' => true, 'order' => 4]);
        $this->get('/deneme/seviye-1')->assertRedirect(route('register'));
        $this->assertDatabaseCount('exam_attempts', 2);
    }

    public function test_slug_and_category_changes_redirect_and_deleted_redirects_stay_deleted(): void
    {
        $exam = $this->exam(); $test = $this->topicTest();
        $exam->update(['slug' => 'yeni-sinav']);
        $this->get('/deneme/seviye-1')->assertRedirect('/deneme/yeni-sinav')->assertStatus(301);
        SeoRedirect::where('old_url', '/deneme/seviye-1')->delete();
        $this->get('/deneme/seviye-1')->assertNotFound();
        $test->update(['slug' => 'yeni-test']);
        $test->category->update(['slug' => 'trafik']);
        $this->get('/konu-testleri/trafik-ve-cevre/seviye-1')->assertRedirect('/konu-testleri/trafik/yeni-test');
        $this->get('/konu-testleri/trafik-ve-cevre/yeni-test')->assertRedirect('/konu-testleri/trafik/yeni-test');
        $other = TopicCategory::create(['name' => 'Diğer', 'slug' => 'diger']);
        $test->update(['topic_category_id' => $other->id]);
        $this->get('/konu-testleri/trafik/yeni-test')->assertRedirect('/konu-testleri/diger/yeni-test');
    }

    public function test_guest_play_urls_open_fresh_for_each_session_without_attempt_urls(): void
    {
        $exam = $this->exam();
        foreach (['first-session', 'different-session'] as $session) {
            $this->withCookie(config('session.cookie'), $session);
            $this->post(route('exams.start', $exam))->assertRedirect(route('exams.play', $exam));
            $this->get(route('exams.play', $exam))->assertOk()->assertSessionMissing('guest_test_attempts')
                ->assertDontSee('/sinav/oturum/', false);
        }
        $this->get(route('exams.take', 'guest-old'))->assertNotFound();
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertDatabaseCount('exam_attempt_answers', 0);
    }
}

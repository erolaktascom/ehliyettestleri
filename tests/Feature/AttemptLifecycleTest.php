<?php
namespace Tests\Feature;

use App\Models\{Exam, ExamAttempt, TopicCategory, TopicTest, User};
use App\Support\PerformanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttemptLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function content(bool $topic = false): Exam|TopicTest
    {
        if ($topic) {
            $category = TopicCategory::create(['name'=>'Kategori', 'slug'=>'kategori', 'is_active'=>true]);
            $test = TopicTest::create(['title'=>'Konu', 'slug'=>'konu', 'topic_category_id'=>$category->id, 'is_published'=>true]);
        } else {
            $test = Exam::create(['title'=>'Deneme', 'slug'=>'deneme', 'is_published'=>true, 'order'=>1, 'duration_minutes'=>45]);
        }
        foreach (range(1, 3) as $i) $test->questions()->create(['question_text'=>'Soru '.$i, 'status'=>'Yayında', 'options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']], 'correct_option'=>'A']);
        return $test;
    }

    public function test_guest_grading_returns_an_ephemeral_result_and_refresh_starts_empty(): void
    {
        foreach ([false, true] as $topic) {
            $test = $this->content($topic);
            $play = $topic ? $test->publicUrl() : route('exams.play', $test);
            $start = route($topic ? 'topic-tests.start' : 'exams.start', $test);
            $grade = route($topic ? 'guest.topic-result' : 'guest.exam-result', $test);
            $this->post($start)->assertRedirect($play)->assertSessionMissing('new_attempt_id');
            $this->get($play)->assertOk()->assertViewHas('gradeUrl', $grade)->assertDontSee('Doğru Cevap');
            $ids = $test->questions->modelKeys();
            $result = $this->postJson($grade, ['question_ids' => $ids, 'answers' => [$ids[0] => 'A', $ids[1] => 'B']])
                ->assertOk()->assertSessionMissing('guest_test_attempts')->json('html');
            foreach (['33', 'Sizin Cevabınız', 'Doğru Cevap', 'Boş Bırakıldı', 'Sonraki sonuçlarını kaydetmek için üye ol'] as $expected) {
                $this->assertStringContainsString($expected, $result);
            }
            $this->get($play)->assertOk()->assertDontSee('Doğru Cevap')->assertSessionMissing('guest_test_attempts');
            $this->assertDatabaseCount('exam_attempts', 0);
            $this->assertDatabaseCount('exam_attempt_answers', 0);
        }
    }

    public function test_guest_grading_rechecks_publication_membership_and_question_list(): void
    {
        $exam = $this->content();
        $url = route('guest.exam-result', $exam);
        $payload = ['question_ids' => $exam->questions->modelKeys(), 'answers' => []];
        $this->postJson($url, array_merge($payload, ['answers' => [99999 => 'A']]))->assertUnprocessable();
        $this->postJson($url, array_merge($payload, ['answers' => [$payload['question_ids'][0] => 'X']]))->assertUnprocessable();
        $this->postJson($url, array_merge($payload, ['question_ids' => []]))->assertStatus(409);
        $exam->update(['order' => 4]);
        $this->postJson($url, $payload)->assertForbidden();
        $exam->update(['is_published' => false]);
        $this->postJson($url, $payload)->assertNotFound();
        $test = $this->content(true);
        $test->category->update(['is_active' => false]);
        $this->postJson(route('guest.topic-result', $test), $payload)->assertNotFound();
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_member_topic_test_resumes_answers_and_last_question_even_after_leaving(): void
    {
        $test = $this->content(true);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('topic-tests.start', $test))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $this->postJson(route('exams.save-answer', $attempt), ['question_id'=>$test->questions->first()->id,'selected_option'=>'B'])->assertOk();
        $this->postJson(route('exams.position', $attempt), ['position'=>2])->assertOk();
        $this->postJson(route('exams.position', $attempt), ['position'=>3])->assertUnprocessable();
        $this->postJson(route('exams.leave', $attempt))->assertOk();
        $this->get($test->listUrl())->assertOk();
        $attempt->update(['expires_at'=>now()->subDay()]);
        $this->post(route('topic-tests.start', $test))->assertRedirect($test->publicUrl());
        $this->get($test->publicUrl())->assertOk()->assertSee('initialQuestionIndex: 2', false)->assertSee('resumeAllowed: true',false)
            ->assertViewHas('answers', fn ($a) => $a->first()->selected_option === 'B');
        $this->assertDatabaseCount('exam_attempts', 1);
        $this->assertSame('in_progress', $attempt->fresh()->status);
        $this->post(route('exams.finish', $attempt))->assertRedirect();
        $this->assertSame('completed', $attempt->fresh()->status);
    }

    public function test_member_exam_exit_and_list_navigation_abandon_without_scoring_and_restart_is_new(): void
    {
        $exam = $this->content();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('exams.start', $exam));
        $first = ExamAttempt::sole();
        $this->postJson(route('exams.save-answer', $first), ['question_id'=>$exam->questions->first()->id,'selected_option'=>'A'])->assertOk();
        $this->postJson(route('exams.leave', $first))->assertOk();
        $this->assertSame('abandoned', $first->fresh()->status);
        $this->assertNull($first->fresh()->completed_at);
        $this->assertSame(0, PerformanceReport::build($user, 'all', 'all')['totalAttempts']);
        $this->post(route('exams.finish', $first))->assertForbidden();
        $this->get($first->playUrl())->assertRedirect($first->playUrl());
        $second = ExamAttempt::latest('id')->first();
        $this->assertNotSame($first->id, $second->id);
        $this->get(route('exams.index'))->assertOk();
        $this->assertSame('abandoned', $second->fresh()->status);
        $this->assertNull($second->fresh()->completed_at);
    }

    public function test_reload_keeps_exam_deadline_and_does_not_complete_it_on_get_after_expiry(): void
    {
        $exam = $this->content();
        $this->actingAs(User::factory()->create())->post(route('exams.start', $exam));
        $attempt = ExamAttempt::sole();
        $before = $attempt->getAttributes();
        $this->get($attempt->playUrl())->assertOk();
        $this->get($attempt->playUrl())->assertOk()->assertSee('resumeAllowed: false',false)->assertSee('freshStart: false',false);
        $this->assertSame($before, $attempt->fresh()->getAttributes());
        $attempt->update(['expires_at'=>now()->subSecond()]);
        $this->get($attempt->playUrl())->assertOk()->assertViewHas('remainingSeconds', 0);
        $this->assertSame('in_progress', $attempt->fresh()->status);
        $this->post(route('exams.finish', $attempt))->assertRedirect();
        $this->assertSame('completed', $attempt->fresh()->status);
    }

    public function test_position_and_exit_are_owned_and_draft_and_member_only_guards_remain(): void
    {
        $exam = $this->content();
        $this->actingAs(User::factory()->create())->post(route('exams.start', $exam));
        $attempt = ExamAttempt::sole();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('exams.position', $attempt), ['position'=>1])->assertForbidden();
        $this->postJson(route('exams.leave', $attempt))->assertForbidden();
        auth()->logout();
        $exam->update(['order'=>4]);
        $this->get(route('exams.play', $exam))->assertRedirect(route('register'));
        $exam->update(['is_published'=>false]);
        $this->get(route('exams.play', $exam))->assertNotFound();
        $this->assertSame([], session('guest_test_attempts', []));
        $this->assertFileDoesNotExist(resource_path('views/pages/exams/ready.blade.php'));
    }

    public function test_abandoned_exams_do_not_affect_panel_scores_topics_or_daily_history(): void
    {
        $exam = $this->content();
        $user = User::factory()->create(['is_admin'=>true]);
        $this->actingAs($user);
        foreach (['abandoned', 'completed'] as $status) {
            $attempt = ExamAttempt::create(['user_id'=>$user->id, 'exam_id'=>$exam->id, 'started_at'=>now(), 'expires_at'=>now()->addMinutes(45),
                'status'=>$status, 'score'=>$status === 'completed' ? 100 : 0, 'is_passed'=>$status === 'completed']);
            $attempt->answers()->create(['question_id'=>$exam->questions->first()->id, 'selected_option'=>$status === 'completed' ? 'A' : 'B', 'is_correct'=>$status === 'completed']);
        }
        $reports = \Livewire\Livewire::test(\App\Livewire\Admin\Reports::class);
        $this->assertSame(1, $reports->viewData('finishedAttempts'));
        $this->assertEquals(100, $reports->viewData('averageScore'));
        $this->assertEquals(1, $reports->viewData('topicStats')->first()->total);
        $this->assertEquals(100, $reports->viewData('topicStats')->first()->success_rate);
        $this->assertEquals(1, array_sum(array_column($reports->viewData('dailyStats'), 'attempts')));
        $dashboard = \Livewire\Livewire::test(\App\Livewire\Admin\Dashboard::class);
        $this->assertCount(0, $dashboard->viewData('difficultQuestions'));
        $this->assertCount(1, $dashboard->viewData('recentAttempts'));
        $this->assertEquals(1, array_sum(array_column($dashboard->viewData('dailyActivity'), 'attempt_count')));
    }
}

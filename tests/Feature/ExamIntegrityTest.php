<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\TopicTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function exam(): Exam
    {
        $exam = Exam::create(['title' => 'Test', 'slug' => 'test', 'is_published' => true, 'order' => 1]);
        $exam->questions()->create([
            'question_text' => 'Test sorusu?', 'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']], 'correct_option' => 'A',
        ]);
        return $exam;
    }

    private function attempt(Exam $exam, ?User $user = null, array $attributes = []): ExamAttempt
    {
        return ExamAttempt::create(array_merge([
            'exam_id' => $exam->id, 'user_id' => $user?->id, 'session_id' => 'another-session',
            'started_at' => now(), 'expires_at' => now()->addMinutes(45),
            'status' => 'in_progress', 'total_questions' => 1,
        ], $attributes));
    }

    public function test_another_user_cannot_read_answer_or_finish_an_attempt(): void
    {
        $exam = $this->exam();
        $attempt = $this->attempt($exam, User::factory()->create());
        $this->actingAs(User::factory()->create());
        $this->get(route('exams.take', $attempt))->assertForbidden();
        $this->get(route('exams.result', $attempt))->assertForbidden();
        $this->get(route('topic-tests.take', $attempt))->assertForbidden();
        $this->postJson(route('exams.save-answer', $attempt), [
            'question_id' => $exam->questions->first()->id, 'selected_option' => 'A',
        ])->assertForbidden();
        $this->post(route('exams.finish', $attempt))->assertForbidden();
        $this->assertSame('in_progress', $attempt->fresh()->status);
        $this->assertDatabaseCount('exam_attempt_answers', 0);
    }

    public function test_guests_cannot_open_other_sessions_or_member_attempts(): void
    {
        $exam = $this->exam();
        $guestAttempt = $this->attempt($exam);
        $this->get(route('exams.take', $guestAttempt))->assertForbidden();
        $memberAttempt = $this->attempt($exam, User::factory()->create(), ['session_id' => session()->getId()]);
        $this->get(route('exams.take', $memberAttempt))->assertForbidden();
    }

    public function test_guest_can_complete_own_exam_and_cannot_see_solutions_early(): void
    {
        $exam = $this->exam();
        $this->post(route('exams.start', $exam))->assertRedirect();
        $question = $exam->questions->sole();
        $question->update(['explanation' => 'Sonuca özel çözüm']);
        $this->get(route('exams.play', $exam))->assertOk()->assertDontSee('Sonuca özel çözüm');
        $result = $this->postJson(route('guest.exam-result', $exam), [
            'question_ids' => [$question->id], 'answers' => [$question->id => 'A'],
        ])->assertOk()->json('html');
        $this->assertStringContainsString('Sonuca özel çözüm', $result);
        $this->assertStringContainsString('100', $result);
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertDatabaseCount('exam_attempt_answers', 0);
    }

    public function test_expired_exam_rejects_answer_and_finalizes(): void
    {
        $exam = $this->exam();
        $user = User::factory()->create();
        $attempt = $this->attempt($exam, $user, ['expires_at' => now()->subSecond()]);
        $this->actingAs($user)->postJson(route('exams.save-answer', $attempt), [
            'question_id' => $exam->questions->first()->id, 'selected_option' => 'A',
        ])->assertForbidden();
        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertDatabaseCount('exam_attempt_answers', 0);
    }

    public function test_unrelated_questions_and_nonexistent_options_are_rejected(): void
    {
        $exam = $this->exam();
        $user = User::factory()->create();
        $attempt = $this->attempt($exam, $user);
        $outside = Question::create(['question_text' => 'Başka soru?', 'options' => [['key' => 'A', 'text' => 'Bir']], 'correct_option' => 'A']);
        $this->actingAs($user)->postJson(route('exams.save-answer', $attempt), [
            'question_id' => $outside->id, 'selected_option' => 'A',
        ])->assertUnprocessable()->assertJsonValidationErrors('question_id');
        $this->postJson(route('exams.save-answer', $attempt), [
            'question_id' => $exam->questions->first()->id, 'selected_option' => 'H',
        ])->assertUnprocessable()->assertJsonValidationErrors('selected_option');
        $this->assertDatabaseCount('exam_attempt_answers', 0);
    }

    public function test_draft_exams_and_topic_tests_cannot_be_started(): void
    {
        $exam = $this->exam();
        $exam->update(['is_published' => false]);
        $category = TopicCategory::create(['name' => 'Kategori', 'slug' => 'kategori']);
        $test = TopicTest::create(['topic_category_id' => $category->id, 'title' => 'Taslak', 'slug' => 'taslak', 'is_published' => false]);
        $this->post(route('exams.start', $exam))->assertNotFound();
        $this->post(route('topic-tests.start', $test))->assertNotFound();
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_topic_test_owner_can_answer_but_cannot_view_results_before_finishing(): void
    {
        $category = TopicCategory::create(['name' => 'Kategori', 'slug' => 'kategori']);
        $test = TopicTest::create(['topic_category_id' => $category->id, 'title' => 'Test', 'slug' => 'konu']);
        $question = $test->questions()->create(['question_text' => 'Soru?', 'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']], 'correct_option' => 'B']);
        $this->actingAs(User::factory()->create())->post(route('topic-tests.start', $test))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $this->get($attempt->playUrl())->assertOk();
        $this->get(route('exams.result', $attempt))->assertRedirect($attempt->playUrl());
        $this->postJson(route('exams.save-answer', $attempt), ['question_id' => $question->id, 'selected_option' => 'B'])->assertOk();
        $this->post(route('exams.finish', $attempt))->assertRedirect();
        $this->assertEquals(100, $attempt->fresh()->score);
    }

    public function test_practice_placeholder_answers_do_not_count_as_answered(): void
    {
        $exam = $this->exam();
        $exam->update(['slug' => 'yanlis-sorular-tekrar-testi', 'is_published' => false]);
        $user = User::factory()->create(['premium_until' => now()->addMonth()]);
        $attempt = $this->attempt($exam, $user);
        $first = $exam->questions->first();
        $second = Question::create(['question_text' => 'İkinci?', 'options' => [['key' => 'A', 'text' => 'Bir']], 'correct_option' => 'A']);
        foreach ([$first, $second] as $question) {
            $attempt->answers()->create(['question_id' => $question->id, 'selected_option' => null, 'is_correct' => false]);
        }
        $this->actingAs($user)->postJson(route('exams.save-answer', $attempt), [
            'question_id' => $first->id, 'selected_option' => 'A',
        ])->assertOk()->assertJson(['answered_count' => 1]);
    }
}

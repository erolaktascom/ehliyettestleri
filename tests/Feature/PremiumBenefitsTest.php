<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdvertisementForm;
use App\Models\{Advertisement, Exam, ExamAttempt, ExamAttemptAnswer, Question, TopicCategory, TopicTest, User};
use App\Support\WrongQuestionPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PremiumBenefitsTest extends TestCase
{
    use RefreshDatabase;

    private function question(array $attributes = []): Question
    {
        return Question::create($attributes + ['question_text' => 'Tekrar sorusu?', 'status' => 'Yayında',
            'options' => [['key' => 'A', 'text' => 'Doğru'], ['key' => 'B', 'text' => 'Yanlış']], 'correct_option' => 'A']);
    }

    private function attempt(User $user, Exam $exam, array $attributes = []): ExamAttempt
    {
        return ExamAttempt::create($attributes + ['user_id' => $user->id, 'exam_id' => $exam->id,
            'status' => 'completed', 'started_at' => now()->subMinutes(10), 'completed_at' => now(),
            'expires_at' => now()->addMinutes(30), 'score' => 80, 'total_questions' => 1]);
    }

    private function answer(User $user, Exam $exam, Question $question, ?string $selected, array $attemptAttributes = []): ExamAttemptAnswer
    {
        return $this->attempt($user, $exam, $attemptAttributes)->answers()->create([
            'question_id' => $question->id, 'selected_option' => $selected, 'is_correct' => $selected === 'A',
        ]);
    }

    public function test_regular_and_expired_members_cannot_start_practice_or_read_report_but_keep_basic_access(): void
    {
        $this->freezeTime();
        foreach ([null, now()->subDay(), now()] as $expiry) {
            $user = User::factory()->create(['premium_until' => $expiry]);
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->get(route('student.saved-questions'))->assertOk();
            $this->get(route('student.detailed-performance'))->assertRedirect(route('student.premium'));
            $this->post(route('student.wrong-questions.start'))->assertRedirect(route('student.premium'));
        }
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertDatabaseMissing('exams', ['slug' => 'yanlis-sorular-tekrar-testi']);
        $this->get(route('student.premium'))->assertOk()->assertSee('Tamamen reklamsız')
            ->assertDontSee('yapay zeka')->assertDontSee('geçme garantisi');
        $premium = User::factory()->create(['premium_until' => now()->addSecond()]);
        $this->actingAs($premium)->get(route('student.detailed-performance'))->assertOk()->assertViewHas('totalAttempts', 0)
            ->assertSee('Bu filtrelerde tamamlanmış test bulunmuyor.');
        $this->post(route('student.wrong-questions.start'))->assertRedirect(route('dashboard'));
    }

    public function test_latest_answer_controls_pool_including_returning_wrong_answers_and_ignoring_blanks_and_unfinished_sessions(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['premium_until' => now()->addMonth()]);
        $other = User::factory()->create();
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme']);
        $question = $this->question();
        $draft = $this->question(['status' => 'Taslak']);
        $blank = $this->question();
        $this->answer($user, $exam, $question, 'B');
        $this->assertSame([$question->id], WrongQuestionPool::pending($user)->pluck('question_id')->all());
        $this->answer($user, $exam, $question, 'A'); // Same second: ID resolves the tie.
        $this->assertSame(0, WrongQuestionPool::pending($user)->count());
        $latestWrong = $this->answer($user, $exam, $question, 'B');
        $backdatedCorrect = $this->answer($user, $exam, $question, 'A');
        $backdatedCorrect->timestamps = false;
        $backdatedCorrect->updated_at = now()->subDay();
        $backdatedCorrect->save(); // Higher ID must not override a later answer timestamp.
        $this->answer($other, $exam, $question, 'A');
        $this->answer($user, $exam, $question, 'A', ['status' => 'in_progress', 'completed_at' => null]);
        $this->answer($user, $exam, $question, null);
        $this->answer($user, $exam, $question, '');
        $this->answer($user, $exam, $blank, null);
        $this->answer($user, $exam, $draft, 'B');
        $this->assertSame([$latestWrong->id], WrongQuestionPool::pending($user)->pluck('id')->all());
        $this->actingAs($user)->get(route('dashboard'))->assertViewHas('unresolvedWrongCount', 1);
        $this->post(route('student.wrong-questions.start'))->assertRedirect();
        $practice = ExamAttempt::latest('id')->firstOrFail();
        $this->assertSame([$question->id], $practice->question_ids);
        $this->assertSame(1, WrongQuestionPool::pending($user)->count()); // Placeholders do not affect pool.
        $this->postJson(route('exams.save-answer', $practice), ['question_id' => $question->id, 'selected_option' => 'A'])->assertOk();
        $this->assertSame(1, WrongQuestionPool::pending($user)->count());
        $this->post(route('exams.finish', $practice))->assertRedirect(route('exams.result', $practice));
        $this->assertSame(0, WrongQuestionPool::pending($user)->count());
    }

    public function test_premium_expiry_blocks_active_repeat_endpoints_but_not_completed_results_or_regular_exams(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['premium_until' => now()->addMinute()]);
        $exam = Exam::create(['title' => 'Tekrar', 'slug' => 'yanlis-sorular-tekrar-testi', 'is_published' => false]);
        $question = $this->question();
        $active = $this->attempt($user, $exam, ['status' => 'in_progress', 'completed_at' => null, 'question_ids' => [$question->id]]);
        $completed = $this->attempt($user, $exam, ['question_ids' => [$question->id]]);
        $this->actingAs($user)->get(route('exams.take', $active))->assertRedirect();
        $this->travel(1)->minutes();
        $this->get(route('exams.take', $active))->assertForbidden();
        $this->postJson(route('exams.save-answer', $active), ['question_id' => $question->id, 'selected_option' => 'A'])->assertForbidden();
        $this->post(route('exams.finish', $active))->assertForbidden();
        $this->get(route('exams.result', $active))->assertForbidden();
        $this->assertSame('in_progress', $active->fresh()->status);
        $this->get(route('exams.result', $completed))->assertOk();
        $regular = Exam::create(['title' => 'Normal', 'slug' => 'normal', 'is_published' => true]);
        $regular->questions()->attach($question);
        $this->post(route('exams.start', $regular))->assertRedirect();
        $this->actingAs(User::factory()->create(['premium_until' => now()->addMonth()]))
            ->get(route('exams.result', $completed))->assertForbidden();
    }

    public function test_report_uses_real_owned_completed_data_filters_and_category_answers(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['premium_until' => now()->addMonth()]);
        $category = TopicCategory::create(['name' => 'İlk Yardım', 'slug' => 'ilk-yardim']);
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme']);
        $test = TopicTest::create(['title' => 'Konu Testi', 'slug' => 'konu-testi', 'topic_category_id' => $category->id]);
        $question = $this->question(['topic_category_id' => $category->id]);
        $first = $this->attempt($user, $exam, ['score' => 40, 'correct_count' => 1, 'incorrect_count' => 1, 'empty_count' => 1]);
        $first->answers()->create(['question_id' => $question->id, 'selected_option' => 'B', 'is_correct' => false]);
        $second = $this->attempt($user, $exam, ['exam_id' => null, 'topic_test_id' => $test->id, 'score' => 100,
            'is_passed' => true, 'correct_count' => 1]);
        $second->answers()->create(['question_id' => $question->id, 'selected_option' => 'A', 'is_correct' => true]);
        $second->answers()->create(['question_id' => $this->question()->id, 'selected_option' => null, 'is_correct' => false]);
        $this->attempt($user, $exam, ['score' => 10, 'started_at' => now()->subDays(100), 'completed_at' => now()->subDays(100)]);
        $this->attempt($user, $exam, ['status' => 'in_progress', 'completed_at' => null, 'score' => 0]);
        $this->attempt(User::factory()->create(), $exam, ['score' => 0]);
        $repeat = Exam::create(['title' => 'Tekrar', 'slug' => 'yanlis-sorular-tekrar-testi']);
        $this->attempt($user, $repeat, ['score' => 0]);
        $this->actingAs($user)->get(route('student.detailed-performance', ['range' => '30']))->assertOk()
            ->assertViewHas('totalAttempts', 2)->assertViewHas('averageScore', 70)->assertViewHas('passedAttempts', 1)
            ->assertViewHas('correctTotal', 2)->assertViewHas('incorrectTotal', 1)->assertViewHas('emptyTotal', 1)
            ->assertViewHas('categoryPerformance', fn ($rows) => $rows->count() === 1 && $rows[0]->name === 'İlk Yardım'
                && (int) $rows[0]->answered === 2 && $rows[0]->percent === 50)
            ->assertSee(route('exams.result', $second), false);
        $this->get(route('student.detailed-performance', ['range' => '90', 'kind' => 'topic_tests']))
            ->assertOk()->assertViewHas('totalAttempts', 1)->assertViewHas('averageScore', 100);
        $this->get(route('student.detailed-performance', ['range' => '30', 'kind' => 'exams']))
            ->assertOk()->assertViewHas('totalAttempts', 1)->assertViewHas('averageScore', 40);
        $this->get(route('student.detailed-performance'))->assertViewHas('totalAttempts', 3)->assertViewHas('averageScore', 50);
        $this->getJson(route('student.detailed-performance', ['range' => '-1', 'kind' => 'bad']))->assertUnprocessable();
    }

    public function test_report_chronological_chart_trend_and_pagination_keep_filters(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['premium_until' => now()->addMonth()]);
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme']);
        foreach (range(1, 22) as $i) {
            $this->attempt($user, $exam, ['completed_at' => now()->subDays(23 - $i), 'score' => $i]);
        }
        $this->actingAs($user)->get(route('student.detailed-performance', ['range' => '30', 'kind' => 'exams']))
            ->assertOk()->assertViewHas('trend', 5)->assertViewHas('recentAverage', 20)
            ->assertViewHas('chart', fn ($rows) => $rows->count() === 20 && $rows->first()->score === 3.0 && $rows->last()->score === 22.0)
            ->assertViewHas('attempts', fn ($rows) => $rows->count() === 15 && str_contains($rows->nextPageUrl(), 'range=30') && str_contains($rows->nextPageUrl(), 'kind=exams'));
        $this->get(route('student.detailed-performance', ['page' => 2]))->assertOk()
            ->assertViewHas('attempts', fn ($rows) => $rows->count() === 7);
    }

    public function test_ad_form_cannot_target_premium_and_does_not_silently_expand_legacy_audience(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $ad = Advertisement::create(['title' => 'Eski Premium', 'device' => 'desktop', 'location' => 'Header',
            'audience' => ['premium'], 'is_active' => true, 'code' => '<b>Eski</b>']);
        Livewire::test(AdvertisementForm::class, ['id' => $ad->id])->assertSet('audience', [])
            ->call('save')->assertHasErrors('audience')
            ->set('audience', ['premium'])->call('save')->assertHasErrors('audience.0');
        $this->assertSame(['premium'], $ad->fresh()->audience);
        $ad->update(['audience' => ['members', 'premium']]);
        Livewire::test(AdvertisementForm::class, ['id' => $ad->id])->assertSet('audience', ['members'])
            ->call('save')->assertHasNoErrors();
        $this->assertSame(['members'], $ad->fresh()->audience);
    }
}

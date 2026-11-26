<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamForm;
use App\Livewire\Admin\QuestionForm;
use App\Livewire\Admin\TopicTestForm;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\TopicTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TestQuestionOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function question(string $text, ?int $categoryId = null): Question
    {
        return Question::create(['question_text' => $text, 'topic_category_id' => $categoryId,
            'options' => [['key' => 'A', 'text' => 'Evet'], ['key' => 'B', 'text' => 'Hayır']], 'correct_option' => 'A', 'status' => 'Yayında']);
    }

    public function test_question_can_be_reused_in_two_exams_with_independent_order(): void
    {
        $one = $this->question('Birinci soru?');
        $two = $this->question('İkinci soru?');
        $exam = Exam::create(['title' => 'Birinci Test', 'slug' => 'birinci']);
        $other = Exam::create(['title' => 'İkinci Test', 'slug' => 'ikinci']);
        Livewire::test(ExamForm::class, ['id' => $exam->id])->call('addQuestion', $one->id)->call('addQuestion', $two->id)
            ->call('moveQuestionBy', $two->id, -1)->call('save')->assertHasNoErrors();
        Livewire::test(ExamForm::class, ['id' => $other->id])->call('addQuestion', $one->id)->call('addQuestion', $two->id)
            ->call('save')->assertHasNoErrors();
        $this->assertSame([$two->id, $one->id], $exam->questions()->pluck('questions.id')->all());
        $this->assertSame([$one->id, $two->id], $other->questions()->pluck('questions.id')->all());
        $this->assertSame(2, $exam->fresh()->question_count);
        Livewire::test(ExamForm::class, ['id' => $exam->id])->call('removeQuestion', $one->id)->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('questions', ['id' => $one->id]);
        $this->assertSame(2, $other->questions()->count());
        $this->assertSame(1, $exam->fresh()->question_count);
    }

    public function test_drag_order_is_persisted_for_topic_tests_and_shown_to_student(): void
    {
        $category = TopicCategory::create(['name' => 'Kategori', 'slug' => 'kategori']);
        $one = $this->question('İlk soru metni?', $category->id);
        $two = $this->question('İkinci soru metni?', $category->id);
        $test = TopicTest::create(['title' => 'Konu Testi', 'slug' => 'konu', 'topic_category_id' => $category->id]);
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->call('addQuestion', $one->id)->call('addQuestion', $two->id)
            ->call('moveQuestion', $two->id, $one->id)->call('save')->assertHasNoErrors();
        $this->post(route('topic-tests.start', $test))->assertRedirect();
        $attempt = ExamAttempt::latest('id')->firstOrFail();
        $this->assertSame([$two->id, $one->id], $attempt->question_ids);
        $this->get($attempt->playUrl())->assertOk()->assertSeeInOrder([$two->question_text, $one->question_text]);
    }

    public function test_started_attempt_keeps_original_questions_after_test_is_edited(): void
    {
        $old = $this->question('Eski soru?');
        $new = $this->question('Yeni soru?');
        $exam = Exam::create(['title' => 'Test', 'slug' => 'test', 'order' => 1]);
        $exam->questions()->attach($old->id, ['position' => 1]);
        $this->post(route('exams.start', $exam))->assertRedirect();
        $attempt = ExamAttempt::latest('id')->firstOrFail();
        Livewire::test(ExamForm::class, ['id' => $exam->id])->call('removeQuestion', $old->id)->call('addQuestion', $new->id)
            ->call('save')->assertHasNoErrors();
        $this->assertSame([$old->id], $attempt->fresh()->questionQuery()->pluck('id')->all());
        $this->postJson(route('exams.save-answer', $attempt), ['question_id' => $new->id, 'selected_option' => 'A'])->assertUnprocessable();
        $this->postJson(route('exams.save-answer', $attempt), ['question_id' => $old->id, 'selected_option' => 'A'])->assertOk();
        $this->post(route('exams.finish', $attempt))->assertRedirect();
        $this->assertSame(100.0, $attempt->fresh()->score);
    }

    public function test_question_editor_has_no_test_or_order_controls_and_does_not_rewrite_membership(): void
    {
        $question = $this->question('Düzenlenecek soru?');
        $exam = Exam::create(['title' => 'Test', 'slug' => 'test']);
        $exam->questions()->attach($question->id, ['position' => 7]);
        Livewire::test(QuestionForm::class, ['id' => $question->id])->assertDontSee('Sıralama No')
            ->assertDontSee('Ait Olduğu Konu Testi')->assertDontSee('Ait Olduğu Deneme Sınavı')
            ->set('question_text', 'Güncellenmiş soru?')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('exam_question', ['exam_id' => $exam->id, 'question_id' => $question->id, 'position' => 7]);
    }

    public function test_forged_duplicate_missing_and_wrong_category_questions_are_rejected(): void
    {
        $question = $this->question('Geçerli soru?');
        $exam = Exam::create(['title' => 'Test', 'slug' => 'test']);
        Livewire::test(ExamForm::class, ['id' => $exam->id])->set('questionIds', [$question->id, $question->id])
            ->call('save')->assertHasErrors(['questionIds.0']);
        Livewire::test(ExamForm::class, ['id' => $exam->id])->set('questionIds', [99999])
            ->call('save')->assertHasErrors(['questionIds.0']);
        $category = TopicCategory::create(['name' => 'Kategori', 'slug' => 'kategori']);
        $question->update(['status' => 'Yayında']);
        $test = TopicTest::create(['title' => 'Konu Testi', 'slug' => 'konu', 'topic_category_id' => $category->id]);
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->set('questionIds', [$question->id])
            ->call('save')->assertHasErrors(['questionIds']);
        $this->assertDatabaseCount('exam_question', 0);
        $this->assertDatabaseCount('question_topic_test', 0);
    }

    public function test_draft_questions_can_be_added_and_saved_in_draft_or_published_tests(): void
    {
        $category = TopicCategory::create(['name' => 'Trafik', 'slug' => 'trafik']);
        $question = $this->question('Taslak soru?', $category->id);
        $question->update(['status' => 'Taslak']);
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme']);
        $test = TopicTest::create(['title' => 'Konu Testi', 'slug' => 'konu', 'topic_category_id' => $category->id]);
        foreach ([[ExamForm::class, $exam], [TopicTestForm::class, $test]] as [$form, $record]) {
            Livewire::test($form, ['id' => $record->id])
                ->assertViewHas('availableQuestions', fn ($rows) => $rows->contains('id', $question->id))
                ->call('addQuestion', $question->id)->assertHasNoErrors()
                ->set('is_published', false)->call('save')->assertHasNoErrors();
            $this->assertFalse($record->fresh()->is_published);
            $this->assertSame([$question->id], $record->questions()->pluck('questions.id')->all());
            Livewire::test($form, ['id' => $record->id])
                ->set('is_published', true)->call('save')->assertHasNoErrors();
            $this->assertTrue($record->fresh()->is_published);
            $this->assertSame([$question->id], $record->questions()->pluck('questions.id')->all());
            $this->assertSame('Taslak', $question->fresh()->status);
        }
    }
}

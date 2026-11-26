<?php

namespace Tests\Feature;

use App\Livewire\Admin\QuestionForm;
use App\Livewire\Admin\QuestionTopicForm;
use App\Livewire\Admin\QuestionTopics;
use App\Livewire\Admin\Questions;
use App\Livewire\Admin\TopicTestForm;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\TopicTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function category(string $slug = 'animasyonlu-sorular'): TopicCategory
    {
        return TopicCategory::create(['name' => $slug === 'animasyonlu-sorular' ? 'Animasyonlu Sorular' : 'İlk Yardım', 'slug' => $slug]);
    }

    private function question(TopicCategory $category, ?TopicTest $test = null): Question
    {
        $question = Question::create(['topic_category_id' => $category->id,
            'question_text' => 'Örnek kategori sorusu?', 'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki'], ['key' => 'C', 'text' => 'Üç'], ['key' => 'D', 'text' => 'Dört']], 'correct_option' => 'A']);
        if ($test) $test->questions()->attach($question->id, ['position' => 1]);
        return $question;
    }

    public function test_admin_category_is_the_same_category_used_by_tests_and_students(): void
    {
        Livewire::test(QuestionTopicForm::class)->assertDontSee('Animasyonlu / Video Soruları Konusu')
            ->set('name', 'Animasyonlu Sorular')->set('slug', 'animasyonlu-sorular')->call('save')->assertHasNoErrors();
        $category = TopicCategory::where('slug', 'animasyonlu-sorular')->firstOrFail();
        Livewire::test(TopicTestForm::class)->assertViewHas('categories', fn ($categories) => $categories->contains('id', $category->id))
            ->set('topic_category_id', $category->id)->set('section', 'animated')->set('title', 'Animasyon Testi')->set('slug', 'animasyon-testi')
            ->call('save')->assertHasNoErrors();
        $this->get('/animasyonlu-sorular')->assertOk()->assertSee('Animasyon Testi');
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('question_topics'));
    }

    public function test_test_editor_filters_the_question_bank_by_category(): void
    {
        $animated = $this->category();
        $firstAid = $this->category('ilk-yardim');
        $animationQuestion = $this->question($animated);
        $this->question($firstAid);
        Livewire::test(TopicTestForm::class)->set('topic_category_id', $animated->id)
            ->assertViewHas('availableQuestions', fn ($questions) => $questions->count() === 1 && $questions->first()->id === $animationQuestion->id);
        Livewire::test(QuestionForm::class)->set('topic_category_id', $firstAid->id)
            ->set('question_text', '<p>Video sorusu</p>')->assertSet('topic_category_id', $firstAid->id);
    }

    public function test_admin_can_override_the_topic_card_icon_and_restore_automatic_selection(): void
    {
        $category = $this->category('ilk-yardim');
        $category->update(['icon' => 'folder']);
        $category->topicTests()->create(['title' => 'Seviye 1', 'slug' => 'seviye-1', 'section' => 'topic_tests', 'is_published' => true]);
        $this->assertSame('first-aid', $category->studyIcon());

        Livewire::test(QuestionTopicForm::class, ['id' => $category->id])
            ->assertSet('icon', null)
            ->set('icon', 'unknown-icon')->call('save')->assertHasErrors(['icon'])
            ->set('icon', 'parking')->call('save')->assertHasNoErrors()->assertNoRedirect();

        $icon = trim(\Illuminate\Support\Facades\Blade::render('<x-student-icon name="parking" />'));
        $this->get('/konu-testleri')->assertOk()->assertSee($icon, false);
        $this->get(route('topic-tests.category', $category))->assertOk()->assertSee($icon, false);

        Livewire::test(QuestionTopicForm::class, ['id' => $category->id])
            ->assertSet('icon', 'parking')->set('icon', '')->call('save')->assertHasNoErrors();
        $this->assertNull($category->refresh()->icon);
        $this->assertSame('first-aid', $category->studyIcon());
    }

    public function test_bank_question_keeps_category_and_can_be_selected_in_a_test(): void
    {
        $category = $this->category();
        $test = TopicTest::create(['topic_category_id' => $category->id, 'title' => 'Test', 'slug' => 'test']);
        Livewire::test(QuestionForm::class)->set('topic_category_id', $category->id)
            ->set('question_text', 'Kategori sorusu?')->set('options', [['key' => 'A', 'text' => 'Evet'], ['key' => 'B', 'text' => 'Hayır']])
            ->call('save')->assertHasNoErrors();
        $question = Question::firstOrFail();
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->call('addQuestion', $question->id)->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('question_topic_test', ['question_id' => $question->id, 'topic_test_id' => $test->id]);
        Livewire::test(QuestionTopics::class)->assertViewHas('topics', fn ($topics) => $topics->first()->questions_count === 1);
        Livewire::test(Questions::class)->set('selectedTopic', $category->id)
            ->assertViewHas('questions', fn ($questions) => $questions->total() === 1);
    }

    public function test_category_rename_keeps_test_and_question_connections(): void
    {
        $category = $this->category();
        $test = TopicTest::create(['topic_category_id' => $category->id, 'title' => 'Test', 'slug' => 'test']);
        $question = $this->question($category, $test);
        Livewire::test(QuestionTopicForm::class, ['id' => $category->id])->set('name', 'Yeni Kategori Adı')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Yeni Kategori Adı', $test->fresh()->category->name);
        $this->assertSame($category->id, $question->fresh()->topic_category_id);
        $this->assertDatabaseCount('topic_categories', 1);
    }

    public function test_changing_test_category_does_not_change_shared_question_category(): void
    {
        $old = $this->category();
        $new = $this->category('ilk-yardim');
        $test = TopicTest::create(['topic_category_id' => $old->id, 'title' => 'Test', 'slug' => 'test']);
        $question = $this->question($old, $test);
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->set('topic_category_id', $new->id)->call('save')->assertHasErrors(['questionIds']);
        $this->assertSame($old->id, $question->fresh()->topic_category_id);
        $this->assertSame($old->id, $test->fresh()->topic_category_id);
    }

    public function test_used_category_cannot_be_deleted_and_inactive_category_cannot_be_started(): void
    {
        $category = $this->category();
        $test = TopicTest::create(['topic_category_id' => $category->id, 'title' => 'Test', 'slug' => 'test']);
        $question = $this->question($category, $test);
        Livewire::test(QuestionTopics::class)->call('delete', $category->id);
        $this->assertDatabaseHas('topic_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
        Livewire::test(QuestionTopicForm::class, ['id' => $category->id])->set('is_active', false)->call('save')->assertHasNoErrors();
        $this->get('/animasyonlu-sorular')->assertOk()->assertDontSee('href="'.route('topic-tests.start', $test).'"', false);
        $this->post(route('topic-tests.start', $test))->assertNotFound();
    }
}

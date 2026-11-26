<?php

namespace Tests\Feature;

use App\Livewire\Admin\QuestionForm;
use App\Models\{BlogCategory, BlogPost, Exam, Lecture, LectureCategory, Page, Question, TopicCategory, TopicTest, TrafficSign, TrafficSignCategory, User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentCreatorTest extends TestCase
{
    use RefreshDatabase;

    private function question(): Question
    {
        return Question::create([
            'question_text' => 'Ekleyeni kaydedilen soru', 'options' => [['key' => 'A', 'text' => 'A'], ['key' => 'B', 'text' => 'B']], 'correct_option' => 'A',
        ]);
    }

    public function test_all_content_modules_record_the_actor_and_render_attribution(): void
    {
        $this->seed(DatabaseSeeder::class);
        $actor = User::factory()->create(['name' => 'İçeriği Ekleyen', 'is_admin' => true]);
        $this->actingAs($actor);
        $modules = [
            Question::class => 'questions', TopicCategory::class => 'question-topics',
            Lecture::class => 'topics', LectureCategory::class => 'content-categories',
            Exam::class => 'exams', TopicTest::class => 'topic-tests', Page::class => 'pages',
            BlogPost::class => 'blog-posts', BlogCategory::class => 'blog-categories',
            TrafficSign::class => 'traffic-signs', TrafficSignCategory::class => 'traffic-sign-categories',
        ];
        foreach ($modules as $class => $route) {
            $record = $class::firstOrFail()->replicate();
            if (array_key_exists('slug', $record->getAttributes())) {
                $record->slug = 'creator-test-'.$route;
            }
            if (array_key_exists('order', $record->getAttributes())) {
                $record->order = 0;
            }
            if (array_key_exists('published_at', $record->getAttributes())) {
                $record->published_at = now();
            }
            $record->save();
            $record->refresh();
            $this->assertEquals($actor->id, $record->created_by, $class);
            $this->assertSame($actor->name, $record->creator_name, $class);
            $list = $this->get(route('admin.'.$route))->assertOk();
            $form = $this->get(route('admin.'.$route.'.edit', $record->id))->assertOk();
            if (in_array($route, ['pages', 'blog-posts', 'blog-categories', 'content-categories', 'question-topics', 'traffic-sign-categories'], true)) {
                $list->assertDontSee('>Ekleyen</th>', false);
                $form->assertDontSee('Ekleyen:</span>', false);
                if ($route === 'blog-posts') {
                    $list->assertSee('Yazar')->assertSee($record->author_name);
                    Livewire::test(\App\Livewire\Admin\BlogPostForm::class, ['id' => $record->id])
                        ->assertSet('author_name', $record->author_name);
                }
            } else {
                $list->assertSee('>Ekleyen</th>', false)->assertSee($actor->name);
                $form->assertSee('>Ekleyen</span>', false)->assertSee($actor->name);
            }
        }
    }

    public function test_editor_creates_question_and_another_admin_edit_preserves_creator(): void
    {
        $editor = User::factory()->create(['name' => 'Soru Editörü', 'is_admin' => true, 'role_name' => 'Editör']);
        $this->actingAs($editor);
        Livewire::test(QuestionForm::class)
            ->set('question_text', 'Editörün yeni sorusu')
            ->set('options', [['key' => 'A', 'text' => 'Birinci'], ['key' => 'B', 'text' => 'İkinci']])
            ->call('save')->assertHasNoErrors();
        $question = Question::firstOrFail();
        $this->assertEquals($editor->id, $question->created_by);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(QuestionForm::class, ['id' => $question->id])
            ->assertSee('Soru Editörü')->set('question_text', 'Güncellenmiş soru')
            ->call('save')->assertHasNoErrors();
        $this->assertEquals($editor->id, $question->fresh()->created_by);
        $this->assertSame('Soru Editörü', $question->fresh()->creator_name);
    }

    public function test_supplied_creator_cannot_override_authenticated_actor_or_original_creator(): void
    {
        $actor = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->create(['is_admin' => true]);
        $this->actingAs($actor);
        $question = new Question(['question_text' => 'Soru', 'options' => [['key' => 'A', 'text' => 'A'], ['key' => 'B', 'text' => 'B']], 'correct_option' => 'A']);
        $question->created_by = $other->id;
        $question->creator_name = 'Sahte isim';
        $question->save();
        $this->assertEquals($actor->id, $question->fresh()->created_by);
        $this->assertSame($actor->name, $question->fresh()->creator_name);
        $this->actingAs($other);
        $question->created_by = $other->id;
        $question->creator_name = 'Değiştirilmiş isim';
        $question->question_text = 'Yeni metin';
        $question->save();
        $this->assertEquals($actor->id, $question->fresh()->created_by);
        $this->assertSame($actor->name, $question->fresh()->creator_name);
    }

    public function test_unknown_legacy_creator_stays_unknown_after_editing(): void
    {
        $question = $this->question();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $question->update(['question_text' => 'Eski soru güncellendi']);
        $this->assertNull($question->fresh()->created_by);
        $this->assertNull($question->fresh()->creator_name);
        $this->get(route('admin.questions.edit', $question->id))->assertOk()->assertSee('Kayıt yok');
    }

    public function test_creator_name_survives_account_rename_and_deletion(): void
    {
        $actor = User::factory()->create(['name' => 'İlk Ekleyen', 'is_admin' => true]);
        $this->actingAs($actor);
        $question = $this->question();
        $actor->update(['name' => 'Yeni Ad']);
        $this->assertSame('İlk Ekleyen', $question->fresh()->creator_name);
        $actor->delete();
        $this->assertNull($question->fresh()->created_by);
        $this->assertSame('İlk Ekleyen', $question->fresh()->creator_name);
    }

    public function test_blog_editor_is_the_creator_and_students_are_not_recorded_as_staff(): void
    {
        $actor = User::factory()->create(['is_admin' => true]);
        $this->actingAs($actor);
        $post = BlogPost::create(['title' => 'Yazı', 'slug' => 'yazi', 'author_name' => 'Konuk Yazar', 'content' => 'İçerik']);
        $this->assertSame($actor->name, $post->author_name);
        $this->assertSame($actor->name, $post->creator_name);
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $question = $this->question();
        $this->assertNull($question->created_by);
        $this->assertNull($question->creator_name);
    }
}

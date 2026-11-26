<?php

namespace Tests\Feature;

use App\Livewire\Admin\{Questions, Exams, TopicTests, BlogPosts, Topics};
use App\Models\{AdminRole, BlogPost, Exam, ExamAttempt, ExamAttemptAnswer, Lecture, Question, TopicCategory, TopicTest, User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicationControlsTest extends TestCase
{
    use RefreshDatabase;

    private function question(string $status = 'Yayında'): Question
    {
        return Question::create(['question_text' => 'Yayın durumu sorusu', 'status' => $status,
            'options' => [['key' => 'A', 'text' => 'A'], ['key' => 'B', 'text' => 'B']], 'correct_option' => 'A']);
    }

    public function test_boolean_publication_selects_match_stored_status_when_opened(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([
            [\App\Livewire\Admin\ExamForm::class, Exam::class],
            [\App\Livewire\Admin\TopicTestForm::class, TopicTest::class],
            [\App\Livewire\Admin\BlogPostForm::class, BlogPost::class],
            [\App\Livewire\Admin\TopicForm::class, Lecture::class],
        ] as [$form, $model]) {
            $record = $model::firstOrFail();
            foreach ([false, true] as $published) {
                $record->update(['is_published' => $published]);
                $component = Livewire::test($form, ['id' => $record->id])->assertSet('is_published', $published);
                $html = $component->html();
                preg_match('/<select[^>]*id="publication-status"[^>]*>(.*?)<\/select>/s', $html, $matches);
                $this->assertNotEmpty($matches);
                // Alpine compares the stringified boolean with option.value on initial binding.
                $value = $published ? 'true' : 'false';
                $this->assertMatchesRegularExpression('/<option value="'.$value.'"\s+selected\b/', $matches[1]);
                $this->assertStringNotContainsString('value="'.($published ? 'false' : 'true').'" selected', $matches[1]);
            }
        }
    }

    public function test_legacy_active_questions_are_normalized_without_changing_drafts_or_dates(): void
    {
        $legacy = $this->question('active');
        $draft = $this->question('Taslak');
        $date = $legacy->updated_at;
        $migration = require database_path('migrations/2026_10_29_010000_normalize_legacy_question_status.php');
        $migration->up();
        $migration->up();
        $this->assertSame('Yayında', $legacy->fresh()->status);
        $this->assertSame('Taslak', $draft->fresh()->status);
        $this->assertTrue($date->eq($legacy->fresh()->updated_at));
    }

    public function test_publication_changes_keep_all_five_edit_forms_open(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
        foreach ([
            [\App\Livewire\Admin\QuestionForm::class, Question::class, 'status'],
            [\App\Livewire\Admin\ExamForm::class, Exam::class, 'is_published'],
            [\App\Livewire\Admin\TopicTestForm::class, TopicTest::class, 'is_published'],
            [\App\Livewire\Admin\BlogPostForm::class, BlogPost::class, 'is_published'],
            [\App\Livewire\Admin\TopicForm::class, Lecture::class, 'is_published'],
        ] as [$form, $model, $field]) {
            $record = $model::firstOrFail();
            $count = $model::count();
            $component = Livewire::test($form, ['id' => $record->id]);
            foreach ([false, true] as $published) {
                $value = $field === 'status' ? ($published ? 'Yayında' : 'Taslak') : $published;
                $component->set($field, $value)->call('save')->assertHasNoErrors()->assertNoRedirect();
                $this->assertSame($value, $record->fresh()->{$field});
            }
            $this->assertSame($count, $model::count());
        }
    }

    public function test_all_five_lists_toggle_publication_and_filter_drafts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([
            [Questions::class, Question::class, 'status', 'selectedStatus', 'questions'],
            [Exams::class, Exam::class, 'is_published', 'selectedStatus', 'exams'],
            [TopicTests::class, TopicTest::class, 'is_published', 'selectedStatus', 'tests'],
            [BlogPosts::class, BlogPost::class, 'is_published', 'filterStatus', 'posts'],
            [Topics::class, Lecture::class, 'is_published', 'filterStatus', 'topics'],
        ] as [$component, $model, $field, $filter, $viewKey]) {
            $record = $model::firstOrFail();
            $published = $field === 'status' ? 'Yayında' : true;
            $draft = $field === 'status' ? 'Taslak' : false;
            $record->update([$field => $published]);
            $creator = $record->creator_name;
            $list = Livewire::test($component)->call('toggleActive', $record->id)->assertHasNoErrors();
            $this->assertSame($draft, $record->fresh()->{$field});
            $this->assertSame($creator, $record->fresh()->creator_name);
            $list->set($filter, $field === 'status' ? 'Taslak' : '0')
                ->assertViewHas($viewKey, fn ($rows) => $rows->contains('id', $record->id))
                ->call('toggleActive', $record->id)
                ->assertViewHas($viewKey, fn ($rows) => !$rows->contains('id', $record->id));
            $this->assertSame($published, $record->fresh()->{$field});
        }
    }

    public function test_lecture_and_first_blog_publication_are_updated(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $lecture = Lecture::firstOrFail();
        Livewire::test(Topics::class)->call('toggleActive', $lecture->id);
        $this->assertFalse($lecture->fresh()->is_published);
        $post = BlogPost::create(['title' => 'Taslak yazı', 'slug' => 'taslak-yazi', 'is_published' => false]);
        Livewire::test(BlogPosts::class)->call('toggleActive', $post->id);
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);
        $publishedAt = $post->fresh()->published_at;
        Livewire::test(BlogPosts::class)->call('toggleActive', $post->id);
        $this->assertTrue($publishedAt->eq($post->fresh()->published_at));
        $this->get(route('blog.show', $post))->assertNotFound();
    }

    public function test_new_attempts_exclude_draft_questions_and_old_attempts_keep_their_snapshot(): void
    {
        $this->actingAs(User::factory()->create());
        $live = $this->question();
        $draft = $this->question('Taslak');
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme', 'is_published' => true]);
        $category = TopicCategory::create(['name' => 'Trafik', 'slug' => 'trafik']);
        $test = TopicTest::create(['title' => 'Test', 'slug' => 'test', 'topic_category_id' => $category->id, 'is_published' => true]);
        foreach ([$exam, $test] as $parent) {
            $parent->questions()->attach([$draft->id => ['position' => 1], $live->id => ['position' => 2]]);
        }
        foreach ([['exams.start', $exam], ['topic-tests.start', $test]] as [$route, $parent]) {
            $this->post(route($route, $parent))->assertRedirect();
            $attempt = ExamAttempt::latest('id')->firstOrFail();
            $this->assertSame([$live->id], $attempt->question_ids);
            $this->assertEquals(1, $attempt->total_questions);
        }
        $live->update(['status' => 'Taslak']);
        $this->assertSame([$live->id], $attempt->fresh()->questionQuery()->pluck('id')->all());
        $count = ExamAttempt::count();
        $this->post(route('exams.start', $exam))->assertSessionHas('error');
        $this->post(route('topic-tests.start', $test))->assertSessionHas('error');
        $this->assertSame($count, ExamAttempt::count());
    }

    public function test_draft_questions_are_hidden_from_saved_questions_and_new_practice(): void
    {
        $user = User::factory()->create(['premium_until' => now()->addMonth()]);
        $this->actingAs($user);
        $draft = $this->question('Taslak');
        $live = $this->question();
        $user->savedQuestions()->create(['question_id' => $draft->id]);
        $user->savedQuestions()->create(['question_id' => $live->id]);
        $this->get(route('student.saved-questions'))->assertViewHas('savedQuestions',
            fn ($rows) => $rows->pluck('question_id')->all() === [$live->id]);
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme']);
        $attempt = ExamAttempt::create(['user_id' => $user->id, 'exam_id' => $exam->id,
            'status' => 'completed', 'completed_at' => now(),
            'question_ids' => [$draft->id, $live->id], 'started_at' => now(), 'expires_at' => now()->addHour()]);
        foreach ([$draft, $live] as $question) {
            ExamAttemptAnswer::create(['exam_attempt_id' => $attempt->id, 'question_id' => $question->id,
                'selected_option' => 'B', 'is_correct' => false]);
        }
        $this->post(route('student.wrong-questions.start'))->assertRedirect();
        $practice = ExamAttempt::latest('id')->firstOrFail();
        $this->assertSame([$live->id], $practice->question_ids);
    }

    public function test_revoked_editor_cannot_replay_a_publication_toggle(): void
    {
        $this->seed(DatabaseSeeder::class);
        $editor = User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']);
        $post = BlogPost::firstOrFail();
        $original = $post->is_published;
        $response = $this->actingAs($editor)->get(route('admin.blog-posts'))->assertOk();
        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        AdminRole::where('key', 'editor')->firstOrFail()->update(['permissions' => []]);
        $this->postJson('/livewire/update', ['components' => [[
            'snapshot' => html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), 'updates' => [],
            'calls' => [['path' => '', 'method' => 'toggleActive', 'params' => [$post->id]]],
        ]]], ['X-Livewire' => 'true'])->assertForbidden();
        $this->assertSame($original, $post->fresh()->is_published);
    }
}

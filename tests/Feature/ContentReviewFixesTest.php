<?php

namespace Tests\Feature;

use App\Livewire\Admin\{ExamForm, Exams, QuestionForm, TopicTestForm, TopicTests};
use App\Models\{BlogPost, Exam, ExamAttempt, Lecture, Question, TopicCategory, TopicTest, User};
use App\Support\QuestionMedia;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

class ContentReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private function question(array $attributes = []): Question
    {
        return Question::create($attributes + ['question_text' => 'Bir soru?', 'options' => [['key' => 'A', 'text' => 'Evet'], ['key' => 'B', 'text' => 'Hayır']], 'correct_option' => 'A', 'status' => 'Yayında']);
    }

    public function test_first_publication_is_separate_from_creation_and_is_preserved_on_republication(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach ([Question::class, Exam::class, TopicTest::class, BlogPost::class, Lecture::class] as $model) {
            $record = $model::firstOrFail()->replicate();
            if ($record->slug) $record->slug .= '-dates';
            $field = $model === Question::class ? 'status' : 'is_published';
            $record->{$field} = $model === Question::class ? 'Taslak' : false;
            $record->save();
            $this->assertNull($record->first_published_at);
            $created = $record->created_at->copy();
            $this->travel(1)->days();
            $record->{$field} = $model === Question::class ? 'Yayında' : true;
            $record->save();
            $first = $record->fresh()->first_published_at;
            $this->assertTrue($first->gt($created));
            $this->assertStringContainsString('Yayınlanmış', Blade::render('<x-admin.record-date :record="$record" />', compact('record')));
            $this->travel(1)->days();
            $record->{$field} = $model === Question::class ? 'Taslak' : false;
            $record->save();
            $record->{$field} = $model === Question::class ? 'Yayında' : true;
            $record->save();
            $this->assertTrue($record->fresh()->first_published_at->eq($first));
            $this->assertTrue($record->fresh()->created_at->eq($created));
            $this->assertNull($record->fresh()->content_updated_at);
            $contentField = $model === Question::class ? 'question_text' : 'title';
            $record->{$contentField} .= ' düzenlendi';
            $record->save();
            $this->assertStringContainsString('Son Güncelleme', Blade::render('<x-admin.record-date :record="$record" />', compact('record')));
            $this->travelBack();
        }
    }

    public function test_reading_a_blog_increments_views_without_changing_edit_date(): void
    {
        $this->seed(DatabaseSeeder::class);
        $post = BlogPost::where('is_published', true)->firstOrFail();
        $date = $post->updated_at->copy();
        $views = $post->views_count;
        $this->travel(1)->days();
        $this->get(route('blog.show', $post))->assertOk();
        $this->assertEquals($views + 1, $post->fresh()->views_count);
        $this->assertTrue($date->eq($post->fresh()->updated_at));
        $this->assertNull($post->fresh()->content_updated_at);
    }

    public function test_saving_unchanged_forms_does_not_mark_content_as_edited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
        foreach ([
            [QuestionForm::class, Question::class], [ExamForm::class, Exam::class],
            [TopicTestForm::class, TopicTest::class], [\App\Livewire\Admin\BlogPostForm::class, BlogPost::class],
            [\App\Livewire\Admin\TopicForm::class, Lecture::class],
        ] as [$form, $model]) {
            $record = $model::firstOrFail();
            \Illuminate\Support\Facades\DB::table($record->getTable())->where('id', $record->id)->update(['content_updated_at' => null]);
            $this->travel(1)->days();
            Livewire::test($form, ['id' => $record->id])->call('save')->assertHasNoErrors();
            $this->assertNull($record->fresh()->last_edited_by);
            $this->assertNull($record->fresh()->content_updated_at, $model.' unchanged form '.json_encode(array_diff_assoc(array_map('serialize', $record->fresh()->getAttributes()), array_map('serialize', $record->getAttributes())), JSON_UNESCAPED_UNICODE));
            $this->travelBack();
        }
    }

    public function test_question_reordering_tracks_a_content_edit_but_repeated_save_does_not(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $category = TopicCategory::create(['name' => 'Kategori', 'slug' => 'kategori']);
        $one = $this->question(['topic_category_id' => $category->id]);
        $two = $this->question(['topic_category_id' => $category->id]);
        foreach ([[Exam::class, ExamForm::class], [TopicTest::class, TopicTestForm::class]] as [$model, $form]) {
            $record = $model::create(['title' => 'Sıra', 'slug' => 'sira', 'topic_category_id' => $category->id]);
            $record->questions()->sync([$one->id => ['position' => 1], $two->id => ['position' => 2]]);
            $this->travel(1)->days();
            Livewire::test($form, ['id' => $record->id])->call('moveQuestionBy', $two->id, -1)->call('save')->assertHasNoErrors();
            $date = $record->fresh()->content_updated_at;
            $this->assertNotNull($date);
            $editorId = auth()->id();
            $this->assertEquals($editorId, $record->fresh()->last_edited_by);
            $this->actingAs(User::factory()->create(['is_admin' => true]));
            $this->travel(1)->days();
            Livewire::test($form, ['id' => $record->id])->call('save')->assertHasNoErrors();
            $this->assertTrue($date->eq($record->fresh()->content_updated_at));
            $this->assertEquals($editorId, $record->fresh()->last_edited_by);
            $this->travelBack();
        }
    }

    public function test_legacy_draft_without_a_date_gets_one_when_published(): void
    {
        $question = $this->question();
        \Illuminate\Support\Facades\DB::table('questions')->where('id', $question->id)
            ->update(['first_published_at' => null, 'publication_history_unknown' => true]);
        $question->refresh()->update(['status' => 'Taslak']);
        $this->travel(1)->days();
        $question->update(['status' => 'Yayında']);
        $this->assertTrue($question->fresh()->first_published_at->eq(now()->startOfSecond()));
        $this->assertFalse($question->fresh()->publication_history_unknown);
    }

    public function test_counts_duration_and_draft_links_match_published_content(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $category = TopicCategory::create(['name' => 'Kategori', 'slug' => 'kategori']);
        $live = $this->question(['topic_category_id' => $category->id]);
        $draft = $this->question(['topic_category_id' => $category->id, 'status' => 'Taslak']);
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme', 'duration_minutes' => 18, 'passing_score' => 60, 'is_published' => true]);
        $exam->questions()->sync([$live->id, $draft->id]);
        $test = TopicTest::create(['title' => 'Konu', 'slug' => 'konu', 'topic_category_id' => $category->id, 'is_published' => false]);
        $test->questions()->sync([$live->id, $draft->id]);
        Livewire::test(Exams::class)->assertSee('1 yayında')->assertSee('1 taslak');
        Livewire::test(TopicTests::class)->assertSee('1 yayında')->assertSee('1 taslak');
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->assertDontSee('Canlı Test Bağlantısı')
            ->assertDontSee('Süre (Dakika - Opsiyonel)')->call('save')->assertHasNoErrors();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('topic_tests', 'duration_minutes'));
        $this->get(route('exams.index'))->assertOk()->assertSee('Deneme')->assertDontSee('1 Soru • 18 Dakika • Baraj: 60');
        $exam->update(['is_published' => false]);
        Livewire::test(ExamForm::class, ['id' => $exam->id])->assertDontSee('Öğrenciler bu sınavı sitede çözebilir');
    }

    public function test_embed_supports_providers_arbitrary_frames_and_native_video_and_strips_active_attributes(): void
    {
        foreach ([
            'https://youtu.be/abc123' => 'https://www.youtube.com/embed/abc123',
            'https://www.youtube.com/watch?v=abc123' => 'https://www.youtube.com/embed/abc123',
            'https://vimeo.com/12345' => 'https://player.vimeo.com/video/12345',
            '<iframe src="https://example.org/player/1" onload="bad()" srcdoc="bad"></iframe>' => 'https://example.org/player/1',
            '/videos/soru.mp4' => '<video',
            '<video><source src="https://example.org/stream"></video>' => '<video',
        ] as $input => $expected) {
            $html = QuestionMedia::embed($input);
            $this->assertStringContainsString($expected, $html);
            $this->assertStringNotContainsString('onload', $html);
            $this->assertStringNotContainsString('srcdoc', $html);
        }
        $clean = QuestionMedia::clean('<p><strong>Soru?</strong><script>alert(1)</script><img src="x" onerror="bad()"></p><iframe src="javascript:alert(1)"></iframe>');
        $this->assertStringContainsString('<strong>Soru?</strong>', $clean);
        foreach (['script', 'onerror', 'javascript:', '<iframe'] as $unsafe) $this->assertStringNotContainsString($unsafe, $clean);
        $this->expectException(\InvalidArgumentException::class);
        QuestionMedia::embed('javascript:alert(1)');
    }

    public function test_editor_embed_survives_save_reopen_and_both_student_players(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $category = TopicCategory::create(['name' => 'Animasyon', 'slug' => 'animasyon']);
        $q = $this->question(['topic_category_id' => $category->id]);
        Livewire::test(QuestionForm::class, ['id' => $q->id])
            ->set('question_text', $q->question_text.QuestionMedia::embed('<iframe src="https://example.org/player" onload="bad()"></iframe>').QuestionMedia::embed('/videos/demo.mp4'))
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $html = $q->fresh()->question_text;
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('<video', $html);
        Livewire::test(QuestionForm::class, ['id' => $q->id])->assertSet('question_text', $html)
            ->call('resolveEditorVideo', 'javascript:alert(1)')->assertReturned(['error' => 'Geçerli bir http/https bağlantısı girin. Yerel dosyalarda / ile başlayan video yolu kullanın.']);
        foreach ([Exam::class, TopicTest::class] as $model) {
            $parent = $model::create(['title' => 'Test', 'slug' => 'test', 'topic_category_id' => $category->id, 'is_published' => true]);
            $parent->questions()->sync([$q->id]);
            $prefix = $model === Exam::class ? 'exams' : 'topic-tests';
            $this->post(route($prefix.'.start', $parent))->assertRedirect();
            $attempt = ExamAttempt::latest('id')->firstOrFail();
            $this->get(route($prefix.'.take', $attempt))->assertRedirect($attempt->playUrl());
            $this->get($attempt->playUrl())->assertOk()->assertSee('<iframe', false)->assertSee('<video', false)
                ->assertDontSee('Gerçek sınavda video oynatıcıda çalışır.')->assertDontSee('onload="bad()"', false);
        }
    }

    public function test_all_rich_editor_forms_resolve_video_without_saving_or_changing_the_content(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([QuestionForm::class, \App\Livewire\Admin\BlogPostForm::class, \App\Livewire\Admin\PageForm::class, \App\Livewire\Admin\TopicForm::class] as $component) {
            $field = $component === QuestionForm::class ? 'question_text' : 'content';
            $form = Livewire::test($component)->set($field, '<p>Mevcut içerik</p>');
            foreach (['https://youtu.be/abc123', '/videos/demo.mp4', '<iframe src="https://example.org/player" onload="bad()"></iframe>'] as $input) {
                $form->call('resolveEditorVideo', $input)->assertReturned(['html' => QuestionMedia::embed($input)])
                    ->assertSet($field, '<p>Mevcut içerik</p>');
            }
            $form->call('resolveEditorVideo', '')->assertReturned(['error' => 'Video bağlantısı veya embed kodu girin (en fazla 10.000 karakter).']);
        }
    }
}

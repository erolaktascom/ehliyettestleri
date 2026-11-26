<?php

namespace Tests\Feature;

use App\Livewire\Admin\{QuestionForm, ExamForm, TopicTestForm, BlogPostForm, TopicForm, PageForm};
use App\Models\{Question, Exam, TopicTest, BlogPost, Lecture, Page, User, AuditLog};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ContentHistoryPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_edits_wait_for_first_publication_before_showing_an_update_date(): void
    {
        $this->travelTo(now()->startOfMinute());
        $this->seed(DatabaseSeeder::class);
        $editor = User::factory()->create(['is_admin' => true, 'name' => 'Düzenleyen Kişi']);
        $this->actingAs($editor);
        foreach ([
            [Question::class, QuestionForm::class, 'question_text'], [Exam::class, ExamForm::class, 'title'],
            [TopicTest::class, TopicTestForm::class, 'title'], [BlogPost::class, BlogPostForm::class, 'content'],
            [Lecture::class, TopicForm::class, 'content'], [Page::class, PageForm::class, 'content'],
        ] as [$model, $formClass, $field]) {
            $record = $model::firstOrFail()->replicate();
            if ($record->slug) $record->slug = 'draft-history-'.$record->getTable();
            $statusField = in_array($model, [Question::class, Page::class]) ? 'status' : 'is_published';
            $draft = $model === Question::class ? 'Taslak' : ($model === Page::class ? 'draft' : false);
            $published = $model === Question::class ? 'Yayında' : ($model === Page::class ? 'published' : true);
            $record->{$statusField} = $draft;
            $record->published_at = now()->subDays(5);
            $record->save();
            $this->travel(1)->days();
            $form = Livewire::test($formClass, ['id' => $record->id])
                ->set($field, 'Taslakta hazırlanan içerik')->call('save')->assertHasNoErrors()->assertDontSee('Son Güncelleme');
            $this->assertNull($record->fresh()->first_published_at);
            $this->assertNull($record->fresh()->content_updated_at);
            $this->travel(1)->days();
            $first = now();
            $form->set($statusField, $published)->call('save')->assertHasNoErrors()
                ->assertSee($first->format('d.m.Y'))->assertDontSee('Son Güncelleme');
            $this->assertTrue($record->fresh()->first_published_at->equalTo($first));
            $this->travel(1)->days();
            $form->set($field, 'Yayın sonrası düzenlenen içerik')->call('save')->assertHasNoErrors()
                ->assertSee('Son Güncelleme')->assertSee('Son Düzenleyen')->assertSee($editor->name);
            $this->assertTrue($record->fresh()->content_updated_at->equalTo(now()));
            $this->assertTrue($record->fresh()->first_published_at->equalTo($first));
        }
    }

    public function test_all_six_panel_forms_show_saved_editor_and_dates_immediately_and_after_reopening(): void
    {
        $this->travelTo(now()->startOfMinute());
        $this->seed(DatabaseSeeder::class);
        $creator = User::factory()->create(['is_admin' => true, 'name' => 'İlk Yazar']);
        $editor = User::factory()->create(['is_admin' => true, 'name' => 'Son Düzenleyen Test']);
        $this->actingAs($creator);
        foreach ([
            [Question::class, QuestionForm::class, 'questions', 'question_text'],
            [Exam::class, ExamForm::class, 'exams', 'title'],
            [TopicTest::class, TopicTestForm::class, 'topic-tests', 'title'],
            [BlogPost::class, BlogPostForm::class, 'blog-posts', 'content'],
            [Lecture::class, TopicForm::class, 'topics', 'content'],
            [Page::class, PageForm::class, 'pages', 'content'],
        ] as [$model, $formClass, $route, $field]) {
            $this->actingAs($creator);
            $record = $model::firstOrFail()->replicate();
            if ($record->slug) $record->slug = 'history-check-'.$route;
            $record->save();
            $record->refresh();
            // Normalize form-derived counts before checking subsequent edits.
            Livewire::test($formClass, ['id' => $record->id])->call('save')->assertHasNoErrors();
            $record->refresh();
            $first = $record->first_published_at;
            $this->assertNotNull($first, $model);
            $this->travel(1)->days();
            $this->actingAs($editor);
            $edited = now();
            $form = Livewire::test($formClass, ['id' => $record->id]);
            $form->set($field, 'Panelde değiştirilen içerik '.$route)->call('save')
                ->assertHasNoErrors()->assertNoRedirect()
                ->assertSee('İlk Yayınlanma')->assertSee($first->format('d.m.Y'))
                ->assertSee('Son Güncelleme')->assertSee($edited->format('d.m.Y'))
                ->assertSee('Son Düzenleyen')->assertSee($editor->name);
            $record->refresh();
            $this->assertTrue($record->first_published_at->equalTo($first), $model);
            $this->assertTrue($record->content_updated_at->equalTo($edited), $model);
            $this->assertEquals($editor->id, $record->last_edited_by, $model);
            $this->assertEquals($creator->id, $record->created_by, $model);
            $this->get(route('admin.'.$route.'.edit', $record->id))->assertOk()
                ->assertSee('Son Güncelleme')->assertSee($edited->format('d.m.Y'))->assertSee($editor->name);
            $this->get(route('admin.'.$route))->assertOk()->assertSee('Son Güncelleme')->assertSee($edited->format('d.m.Y'));
            $this->travel(1)->days();
            $this->actingAs($creator);
            Livewire::test($formClass, ['id' => $record->id])->call('save')->assertHasNoErrors();
            $this->assertTrue($record->fresh()->content_updated_at->equalTo($edited), $model);
            $this->assertEquals($editor->id, $record->fresh()->last_edited_by, $model);
        }
    }

    public function test_publication_date_changes_are_edits_but_minute_precision_does_not_create_false_edits(): void
    {
        $this->travelTo(now()->startOfMinute()->addSeconds(37));
        $this->seed(DatabaseSeeder::class);
        $editor = User::factory()->create(['is_admin' => true, 'name' => 'Tarih Düzenleyen']);
        $this->actingAs($editor);
        foreach ([Page::class => PageForm::class, BlogPost::class => BlogPostForm::class,
            Question::class => QuestionForm::class, Exam::class => ExamForm::class,
            TopicTest::class => TopicTestForm::class, Lecture::class => TopicForm::class] as $model => $formClass) {
            $record = $model::firstOrFail();
            DB::table($record->getTable())->where('id', $record->id)->update([
                'published_at' => now()->subDays(3), 'first_published_at' => now()->subDays(3),
                'content_updated_at' => null, 'last_edited_by' => null, 'last_editor_name' => null,
            ]);
            $record->refresh();
            $first = $record->first_published_at;
            $form = Livewire::test($formClass, ['id' => $record->id])->call('save')->assertHasNoErrors();
            $this->assertNull($record->fresh()->content_updated_at);
            $this->assertNull($record->fresh()->last_edited_by);
            $this->assertTrue($record->fresh()->published_at->equalTo($first));
            $form->set('published_at', now()->subDays(2)->format('Y-m-d\TH:i'))->call('save')->assertHasNoErrors()
                ->assertSee('Son Güncelleme')->assertSee('Son Düzenleyen')->assertSee($editor->name);
            $this->assertTrue($record->fresh()->content_updated_at->equalTo(now()));
            $this->assertTrue($record->fresh()->first_published_at->equalTo($first));
            $this->assertEquals($editor->id, $record->fresh()->last_edited_by);
            $firstEdit = $record->fresh()->content_updated_at;
            $this->travel(1)->hours();
            Livewire::test($formClass, ['id' => $record->id])
                ->assertSet('published_at', $record->fresh()->published_at->format('Y-m-d\TH:i'))
                ->assertSee('Son Düzenleyen')->assertSee($editor->name)
                ->call('save')->assertHasNoErrors()->assertNoRedirect();
            $this->assertTrue($record->fresh()->content_updated_at->equalTo($firstEdit));
            Livewire::test($formClass, ['id' => $record->id])->set('published_at', 'geçersiz tarih')
                ->call('save')->assertHasErrors(['published_at']);
            $this->assertTrue($record->fresh()->content_updated_at->equalTo($firstEdit));
        }
    }

    public function test_repair_uses_verified_audit_and_does_not_overwrite_later_history(): void
    {
        $this->travelTo(now()->startOfMinute());
        $editor = User::factory()->create(['is_admin' => true]);
        $page = Page::create(['title' => 'Geçmiş', 'slug' => 'gecmis']);
        $first = $page->first_published_at;
        $this->travel(1)->days();
        DB::table('pages')->where('id', $page->id)->update(['updated_at' => now(), 'published_at' => now()->subDays(5)]);
        AuditLog::create(['record_type' => 'Sayfa', 'record_title' => 'Geçmiş #'.$page->id,
            'changed_fields' => 'Düzenlendi: Yayın tarihi', 'user_id' => $editor->id, 'user_name' => $editor->name]);
        $migration = require database_path('migrations/2026_10_29_220000_restore_audited_publication_edits.php');
        $migration->up();
        $this->assertTrue($page->fresh()->content_updated_at->equalTo(now()));
        $this->assertEquals($editor->id, $page->fresh()->last_edited_by);
        $this->assertTrue($page->fresh()->first_published_at->equalTo($first));
        $this->travel(1)->days();
        $this->actingAs($editor);
        $page->refresh()->update(['content' => 'Daha sonraki düzenleme']);
        $migration->up();
        $this->assertTrue($page->fresh()->content_updated_at->equalTo(now()));
        $unknown = Page::create(['title' => 'Bilinmeyen', 'slug' => 'bilinmeyen']);
        $migration->up();
        $this->assertNull($unknown->fresh()->content_updated_at);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\{BlogPostForm, ExamForm, PageForm, TopicForm, TopicTestForm};
use App\Models\{BlogPost, Exam, Lecture, Page, TopicTest, User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLiveLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_links_target_saved_content_and_follow_saved_slug_changes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $cases = [
            [Exam::where('is_published', true)->firstOrFail(), ExamForm::class, 'Canlı Deneme Sınavı Linki', fn ($row) => route('exams.play', ['exam' => $row->slug])],
            [TopicTest::where('is_published', true)->firstOrFail(), TopicTestForm::class, 'Canlı Test Bağlantısı', fn ($row) => route('topic-tests.play', ['category' => $row->category->slug, 'test' => $row->slug])],
            [Lecture::where('is_published', true)->firstOrFail(), TopicForm::class, 'Canlı Önizleme Linki', fn ($row) => route('lectures.show', ['category' => $row->category->slug, 'lecture' => $row->slug])],
            [BlogPost::visible()->firstOrFail(), BlogPostForm::class, 'Canlı Blog Bağlantısı', fn ($row) => route('blog.show', ['post' => $row->slug])],
            [Page::where('status', 'published')->firstOrFail(), PageForm::class, 'Canlı Sayfa Bağlantısı', fn ($row) => route('page.show', ['slug' => $row->slug])],
        ];
        foreach ($cases as [$record, $component, $label, $url]) {
            $oldUrl = $url($record);
            $newSlug = $record->slug.'-yeni';
            $form = Livewire::test($component, ['id' => $record->id])->assertSee($label)
                ->assertSee('href="'.$oldUrl.'"', false)->set('slug', $newSlug)
                ->assertSee('href="'.$oldUrl.'"', false);
            $form->call('save')->assertHasNoErrors()->assertNoRedirect();
            $record->refresh();
            $this->assertSame($newSlug, $record->slug);
            $form->assertSee('href="'.$url($record).'"', false);
            if ($record instanceof Exam) $this->post(route('exams.start', $record))->assertRedirect($url($record));
            if ($record instanceof TopicTest) {
                $record->questions()->create(['topic_category_id'=>$record->topic_category_id,'question_text'=>'Canlı bağlantı sorusu','status'=>'Yayında','options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']],'correct_option'=>'A']);
                $this->get($url($record))->assertRedirect($url($record));
            }
            $this->get($url($record))->assertOk();
            $record->update($record instanceof Page ? ['status' => 'draft'] : ['is_published' => false]);
            Livewire::test($component, ['id' => $record->id])->assertDontSee($label);
        }
    }

    public function test_inactive_category_does_not_show_a_broken_topic_test_link(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $test = TopicTest::where('is_published', true)->firstOrFail();
        $test->category->update(['is_active' => false]);
        Livewire::test(TopicTestForm::class, ['id' => $test->id])->assertDontSee('Canlı Test Bağlantısı');
    }
}

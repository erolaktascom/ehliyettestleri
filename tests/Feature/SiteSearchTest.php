<?php

namespace Tests\Feature;

use App\Models\{BlogPost, Exam, Lecture, Page, TopicTest};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_links_to_public_content_and_excludes_drafts_and_hidden_categories(): void
    {
        $this->seed(DatabaseSeeder::class);
        $records = [Exam::where('is_published', true)->firstOrFail(), TopicTest::where('is_published', true)->firstOrFail(),
            Lecture::where('is_published', true)->firstOrFail(), BlogPost::visible()->firstOrFail(), Page::where('status', 'published')->firstOrFail()];
        foreach ($records as $i => $record) $record->update(['title' => 'Arama denemesi '.$i]);
        $response = $this->get('/arama?q=Arama')->assertOk();
        $results = $response->viewData('results');
        $this->assertSame(5, $results->total());
        $this->actingAs(\App\Models\User::factory()->create());
        foreach ([$records[0], $records[1]] as $test) {
            $test->questions()->create(['question_text'=>'Arama bağlantısı sorusu','status'=>'Yayında','options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']],'correct_option'=>'A']);
        }
        $playUrls = array_map(fn ($test) => url(\App\Support\AutomaticSlugRedirects::publicPath($test)), [$records[0], $records[1]]);
        foreach ($results as $result) {
            if (in_array($result->url, $playUrls, true)) {
                $this->get($result->url)->assertRedirect($result->url);
                $this->get($result->url)->assertOk()->assertViewHas('attempt')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            } else {
                $this->get($result->url)->assertOk();
            }
        }
        $this->assertDatabaseCount('exam_attempts', 2);
        $records[1]->category->update(['is_active' => false]);
        $this->assertSame(4, $this->get('/arama?q=Arama')->viewData('results')->total());
        $records[1]->category->update(['is_active' => true]);
        $records[3]->update(['published_at' => now()->addDay()]);
        $this->assertSame(4, $this->get('/arama?q=Arama')->viewData('results')->total());
        foreach ($records as $record) $record->update($record instanceof Page ? ['status' => 'draft'] : ['is_published' => false]);
        $this->get('/arama?q=Arama')->assertOk()->assertSee('Aradığın içerik bulunamadı.')
            ->assertViewHas('results', fn ($rows) => $rows->total() === 0);
    }

    public function test_search_handles_empty_input_literal_wildcards_and_pagination(): void
    {
        $this->get('/arama')->assertOk()->assertViewHas('results', null);
        $this->get('/arama?q[]=invalid')->assertOk()->assertViewHas('query', '');
        foreach (range(1, 14) as $i) Exam::create(['title' => 'Bulunacak test '.$i, 'slug' => 'test-'.$i, 'is_published' => true]);
        $first = $this->get('/arama?q=Bulunacak')->assertOk()->viewData('results');
        $this->assertSame(14, $first->total());
        $this->assertCount(12, $first);
        $this->assertStringContainsString('q=Bulunacak', $first->nextPageUrl());
        $this->assertCount(2, $this->get($first->nextPageUrl())->assertOk()->viewData('results'));
        $this->get('/arama?q=%25')->assertViewHas('results', fn ($rows) => $rows->total() === 0);
        $payload = '<script>alert("search")</script>';
        $this->get('/arama?q='.rawurlencode($payload))->assertOk()
            ->assertSee(e($payload), false)->assertDontSee($payload, false);
    }

    public function test_404_keeps_status_and_uses_site_navigation_footer_and_working_search_form(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->get('/missing-site-page')->assertNotFound()
            ->assertSee('Bu yol bir yere')->assertSee('Ana Sayfaya Dön')
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('name="q"', false)->assertSee('name="robots" content="noindex, follow"', false)
            ->assertSee('Alt bilgi sütunları')->assertSee('Menüyü aç');
    }
}

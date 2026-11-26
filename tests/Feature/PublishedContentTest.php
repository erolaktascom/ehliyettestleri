<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Lecture;
use App\Models\LectureCategory;
use App\Models\TopicCategory;
use App\Models\TopicTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_blog_post_cannot_be_opened_by_slug(): void
    {
        $post = BlogPost::create(['title' => 'Taslak', 'slug' => 'taslak', 'is_published' => false]);
        $this->get(route('blog.show', $post))->assertNotFound();
        $this->assertEquals(0, $post->fresh()->views_count);
    }

    public function test_draft_lecture_cannot_be_read_or_marked_complete(): void
    {
        $category = LectureCategory::create(['name' => 'Dersler', 'slug' => 'dersler']);
        $lecture = Lecture::create(['lecture_category_id' => $category->id, 'title' => 'Gizli', 'slug' => 'gizli', 'is_published' => false]);
        $this->get(route('lectures.show', [$category, $lecture]))->assertNotFound();
        $this->postJson(route('lectures.toggle-complete', $lecture))->assertUnauthorized();
        $this->actingAs(\App\Models\User::factory()->create())->post(route('lectures.toggle-complete', $lecture))->assertNotFound();
        $this->get(route('lectures.index'))->assertViewHas('totalLecturesCount', 0);
        $this->assertDatabaseCount('user_lecture_progress', 0);
    }

    public function test_published_lecture_completion_is_saved_only_for_the_member(): void
    {
        $category = LectureCategory::create(['name' => 'Dersler', 'slug' => 'dersler']);
        $lecture = Lecture::create(['lecture_category_id' => $category->id, 'title' => 'Ders', 'slug' => 'ders', 'is_published' => true]);
        $toggle = route('lectures.toggle-complete', $lecture);
        $this->postJson($toggle)->assertUnauthorized();
        $this->assertDatabaseCount('user_lecture_progress', 0);
        $member = \App\Models\User::factory()->create();
        $this->actingAs($member)->post($toggle)->assertRedirect();
        $this->assertDatabaseHas('user_lecture_progress', ['user_id' => $member->id, 'lecture_id' => $lecture->id, 'is_completed' => true]);
        $this->get(route('lectures.show', [$category, $lecture]))->assertSee('Tamamlandı işaretini kaldır');
        auth()->logout();
        $this->get(route('lectures.category', $category))->assertOk()->assertDontSee('İlerlemeni kaydetmek için üye ol')->assertDontSee('Kategori Tamamlanma Oranı');
    }

    public function test_lecture_must_belong_to_the_requested_category(): void
    {
        $first = LectureCategory::create(['name' => 'Bir', 'slug' => 'bir']);
        $second = LectureCategory::create(['name' => 'İki', 'slug' => 'iki']);
        $lecture = Lecture::create(['lecture_category_id' => $first->id, 'title' => 'Konu', 'slug' => 'konu']);
        $this->get(route('lectures.show', [$second, $lecture]))->assertNotFound();
        $lecture->update(['seo_title' => 'Ders SEO başlığı', 'seo_description' => 'Derse özel açıklama']);
        $before = $lecture->fresh()->getAttributes();
        $this->get(route('lectures.show', [$first, $lecture]))->assertOk()
            ->assertSee('İlerlemeni kaydetmek için üye ol')->assertDontSee('Konuyu tamamladım')
            ->assertSee('Ders SEO başlığı')->assertSee('Derse özel açıklama');
        $after = $lecture->fresh();
        $this->assertSame(1, $after->view_count);
        foreach (['updated_at', 'content_updated_at', 'first_published_at', 'last_edited_by'] as $field) {
            $this->assertSame($before[$field], $after->getRawOriginal($field));
        }
    }

    public function test_traffic_signs_are_learning_only_and_removed_test_routes_return_404(): void
    {
        $this->get('/trafik-isaretleri')->assertOk()->assertViewIs('pages.traffic-signs.categories')->assertDontSee('Bilgini Test Et')->assertDontSee('/trafik-isaretleri-testleri', false);
        $this->get('/ogren/trafik-isaretleri')->assertStatus(301)->assertRedirect('/trafik-isaretleri');
        $this->get('/trafik-isaretleri-testleri')->assertNotFound();
        $this->get('/trafik-isaretleri-testleri/trafik-isaretleri-test-1')->assertNotFound();
        $this->get('/trafik-isareti-testleri-sitemap.xml')->assertNotFound();
        $this->assertArrayNotHasKey('traffic_signs', \App\Models\TopicTest::SECTIONS);
        $this->assertArrayNotHasKey('traffic-signs.tests', \App\Support\SeoPageCatalog::PAGES);
    }
}

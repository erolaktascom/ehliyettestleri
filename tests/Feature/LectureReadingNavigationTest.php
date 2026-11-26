<?php

namespace Tests\Feature;

use App\Models\{Lecture, LectureCategory};
use App\Support\LectureReadingContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LectureReadingNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_uses_only_published_siblings_and_stable_order(): void
    {
        $category = LectureCategory::create(['name' => 'Dersler', 'slug' => 'dersler']);
        $other = LectureCategory::create(['name' => 'Diğer', 'slug' => 'diger']);
        $make = fn ($slug, $order, $published = true, $cat = null) => Lecture::create([
            'lecture_category_id' => ($cat ?? $category)->id, 'title' => $slug, 'slug' => $slug,
            'order' => $order, 'is_published' => $published,
        ]);
        $first = $make('ilk', 1);
        $middle = $make('orta', 1);
        $make('taslak', 2, false);
        $make('baska', 2, true, $other);
        $last = $make('son', 3);
        $this->get(route('lectures.show', [$category, $middle]))->assertOk()
            ->assertViewHas('previousLecture', fn ($row) => $row->is($first))
            ->assertViewHas('nextLecture', fn ($row) => $row->is($last))
            ->assertSee('Önceki konu')->assertSee(route('lectures.show', [$category, $first]), false);
        $this->get(route('lectures.show', [$category, $first]))->assertViewHas('previousLecture', null)
            ->assertViewHas('nextLecture', fn ($row) => $row->is($middle));
        $this->get(route('lectures.show', [$category, $last]))->assertViewHas('nextLecture', null);
    }

    public function test_contents_links_preserve_text_and_existing_anchors_without_rewriting_database(): void
    {
        $category = LectureCategory::create(['name' => 'Ders', 'slug' => 'ders']);
        $html = '<h2 id="ozel">İlk yardım &amp; güvenlik</h2><p>Türkçe açıklama</p><h3>İlk yardım</h3><h2>İlk yardım</h2><img src="/image.png" alt="Şema">';
        $lecture = Lecture::create(['lecture_category_id' => $category->id, 'title' => 'Uzun', 'slug' => 'uzun', 'is_published' => true, 'content' => $html]);
        $this->get(route('lectures.show', [$category, $lecture]))->assertOk()
            ->assertSee('İçindekiler')->assertSee('href="#ozel"', false)
            ->assertSee('href="#lecture-section-2"', false)->assertSee('id="lecture-section-2"', false)
            ->assertSee('Türkçe açıklama')->assertSee('alt="Şema"', false);
        $this->assertSame($html, $lecture->fresh()->content);
        $short = '<h2>Kısa</h2><p>Yazı</p><h3>Alt başlık</h3>';
        $this->assertSame(['html' => $short, 'headings' => []], LectureReadingContent::prepare($short));
        $duplicate = LectureReadingContent::prepare('<div id="lecture-section-1"></div><h2 id="same">A</h2><h2 id="same">B</h2><h3>C</h3>');
        $anchors = array_column($duplicate['headings'], 'anchor');
        $this->assertCount(3, array_unique($anchors));
        $this->assertNotContains('lecture-section-1', $anchors);
    }
}

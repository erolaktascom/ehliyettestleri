<?php

namespace Tests\Feature;

use App\Livewire\Admin\PageForm;
use App\Models\{Page, NavigationItem, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_search_respects_status_for_title_and_slug_matches(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['draft', 'published'] as $status) {
            Page::create(['title' => 'Aranan başlık '.$status, 'slug' => 'baslik-'.$status, 'status' => $status]);
            Page::create(['title' => 'Bağlantı eşleşmesi '.$status, 'slug' => 'aranan-'.$status, 'status' => $status]);
        }
        $form = Livewire::test(\App\Livewire\Admin\Pages::class)->set('search', 'aranan');
        $form->set('filterStatus', 'draft')->assertSee('Aranan başlık draft')->assertSee('Bağlantı eşleşmesi draft')
            ->assertDontSee('Aranan başlık published')->assertDontSee('Bağlantı eşleşmesi published');
        $form->set('filterStatus', 'published')->assertSee('Aranan başlık published')->assertSee('Bağlantı eşleşmesi published')
            ->assertDontSee('Aranan başlık draft')->assertDontSee('Bağlantı eşleşmesi draft');
        $form->set('filterStatus', '')->assertSee('Aranan başlık draft')->assertSee('Aranan başlık published');
    }

    public function test_page_publication_history_tracks_real_edits_and_preserves_first_publication(): void
    {
        $this->travelTo(now()->startOfMinute());
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $page = Page::create(['title' => 'Tarih kontrolü', 'slug' => 'tarih-kontrolu', 'status' => 'draft', 'content' => 'İlk içerik']);
        $this->assertNull($page->first_published_at);
        $form = Livewire::test(PageForm::class, ['id' => $page->id]);
        $form->assertDontSee('Ekleyen')->assertSee('İlk Yayınlanma')->assertDontSee('Son Güncelleme');
        $this->travel(1)->days();
        $first = now();
        $form->set('status', 'published')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertTrue($page->fresh()->first_published_at->equalTo($first));
        $this->assertNull($page->fresh()->content_updated_at);
        $this->get('/admin/pages')->assertOk()->assertDontSee('Ekleyen')->assertDontSee('Görüntülenme')
            ->assertSee('Yayınlanmış')->assertSee($first->format('d.m.Y'));
        $this->get('/tarih-kontrolu')->assertOk()->assertSee('Yayınlanmış')->assertDontSee('Son Güncelleme');

        $this->travel(1)->days();
        $form->call('save')->assertHasNoErrors();
        $this->assertNull($page->fresh()->content_updated_at);
        $form->set('published_at', now()->subDays(5)->format('Y-m-d\TH:i'))->call('save')->assertHasNoErrors();
        $form->set('status', 'draft')->call('save')->assertHasNoErrors();
        $form->set('status', 'published')->call('save')->assertHasNoErrors();
        $this->assertTrue($page->fresh()->first_published_at->equalTo($first));
        $this->assertTrue($page->fresh()->content_updated_at->equalTo(now()));
        $this->assertEquals($admin->id, $page->fresh()->last_edited_by);

        $edited = now();
        $form->set('content', 'Gerçek içerik değişikliği')->call('save')->assertHasNoErrors()->assertNoRedirect()
            ->assertSee('İlk Yayınlanma')->assertSee($first->format('d.m.Y'))
            ->assertSee('Son Güncelleme')->assertSee($edited->format('d.m.Y'));
        $this->assertTrue($page->fresh()->content_updated_at->equalTo($edited));
        $this->assertEquals($admin->id, $page->fresh()->last_edited_by);
        $this->travel(1)->days();
        $this->get('/tarih-kontrolu')->assertOk()->assertSee('Son Güncelleme')->assertSee($edited->format('d.m.Y'));
        $page->increment('view_count');
        $form->call('save')->assertHasNoErrors();
        $this->assertTrue($page->fresh()->content_updated_at->equalTo($edited));
        $this->assertTrue($page->fresh()->first_published_at->equalTo($first));
        $this->get('/admin/pages')->assertOk()->assertSee('Son Güncelleme')->assertSee($edited->format('d.m.Y'));
    }

    public function test_page_history_migration_preserves_recorded_dates_without_inventing_edits(): void
    {
        $migration = require database_path('migrations/2026_10_29_200000_track_page_publication_history.php');
        $migration->down();
        $published = now()->subDays(10)->startOfMinute();
        $id = \Illuminate\Support\Facades\DB::table('pages')->insertGetId([
            'title' => 'Eski sayfa', 'slug' => 'eski-sayfa', 'status' => 'published',
            'published_at' => $published, 'created_at' => $published, 'updated_at' => now(),
        ]);
        $migration->up();
        $page = Page::findOrFail($id);
        $this->assertTrue($page->first_published_at->equalTo($published));
        $this->assertNull($page->content_updated_at);
        $this->assertNull($page->last_edited_by);
    }

    public function test_faq_is_managed_in_pages_and_navigation_uses_its_public_address(): void
    {
        $faq = Page::where('slug', 'sikca-sorulan-sorular')->firstOrFail();
        $this->assertDatabaseHas('navigation_items', ['name' => 'Sıkça Sorulan Sorular', 'url' => '/sikca-sorulan-sorular']);
        $this->get('/sikca-sorulan-sorular')->assertOk()->assertSee('Konu testlerinde süre sınırı var mı?');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/pages')->assertOk()->assertSee('Sıkça Sorulan Sorular');
        $form = Livewire::test(PageForm::class, ['id' => $faq->id])->set('content', '<h2>Yeni SSS sorusu</h2><p>Paneldeki yanıt.</p>')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->get('/sikca-sorulan-sorular')->assertSee('Yeni SSS sorusu')->assertSee('Paneldeki yanıt.');
        $form->set('status', 'draft')->call('save')->assertHasNoErrors();
        $this->get('/sikca-sorulan-sorular')->assertNotFound();
    }

    public function test_about_uses_the_panel_page_and_reading_does_not_change_its_edit_date(): void
    {
        $about = Page::create(['title' => 'Hakkımızda', 'slug' => 'hakkimizda', 'content' => 'Panelde yazılan hakkımızda.']);
        $this->assertNotNull($about->first_published_at);
        $date = $about->updated_at;
        $this->travel(1)->days();
        $this->get('/hakkimizda')->assertOk()->assertSee('Panelde yazılan hakkımızda.');
        $this->assertTrue($about->fresh()->updated_at->equalTo($date));
        $about->update(['status' => 'draft']);
        $this->get('/hakkimizda')->assertNotFound();
    }

    public function test_faq_migration_preserves_existing_edits_and_menu_visibility(): void
    {
        $faq = Page::where('slug', 'sikca-sorulan-sorular')->firstOrFail();
        $faq->update(['content' => 'Özel SSS içeriği', 'status' => 'draft']);
        $link = NavigationItem::create(['name' => 'SSS', 'url' => '/hakkimizda#sss', 'menu' => 'footer', 'is_active' => false]);
        $migration = require database_path('migrations/2026_10_29_190000_add_faq_page.php');
        $migration->up();
        $this->assertSame('Özel SSS içeriği', $faq->fresh()->content);
        $this->assertSame('draft', $faq->fresh()->status);
        $this->assertSame(1, Page::where('slug', 'sikca-sorulan-sorular')->count());
        $this->assertSame('/sayfa/sikca-sorulan-sorular', $link->fresh()->url);
        (require database_path('migrations/2026_11_02_130000_move_pages_to_root_urls.php'))->up();
        $this->assertSame('/sikca-sorulan-sorular', $link->fresh()->url);
        $this->assertFalse($link->fresh()->is_active);
    }
}

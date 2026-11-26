<?php

namespace Tests\Feature;

use App\Livewire\Admin\PageForm;
use App\Models\{Page, NavigationItem, SeoRedirect, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RootPageUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_use_root_urls_and_drafts_and_unknown_urls_stay_hidden(): void
    {
        Page::create(['title' => 'Adres Denemesi', 'slug' => 'adres-denemesi', 'content' => 'Kök adres içeriği']);
        Page::create(['title' => 'Taslak', 'slug' => 'taslak-adres', 'status' => 'draft']);
        $this->assertSame('/adres-denemesi', route('page.show', ['slug' => 'adres-denemesi'], false));
        $this->get('/adres-denemesi')->assertOk()->assertSee('Kök adres içeriği');
        $this->get('/sayfa/adres-denemesi')->assertNotFound();
        $this->get('/taslak-adres')->assertNotFound();
        $this->get('/olmayan-sayfa')->assertNotFound();
        $this->get('/blog')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_migration_moves_navigation_and_creates_deletable_direct_redirects_without_changing_content_dates(): void
    {
        $page = Page::create(['title' => 'Geçiş', 'slug' => 'gecis-sayfasi', 'content' => 'İçerik']);
        $original = $page->fresh()->getAttributes();
        $nav = NavigationItem::create(['name' => 'İç link', 'url' => '/sayfa/gecis-sayfasi?ref=footer#bilgi', 'menu' => 'footer', 'is_active' => false]);
        $external = NavigationItem::create(['name' => 'Dış link', 'url' => 'https://example.com/sayfa/gecis-sayfasi', 'menu' => 'footer']);
        (require database_path('migrations/2026_11_02_130000_move_pages_to_root_urls.php'))->up();
        $this->assertSame($original, $page->fresh()->getAttributes());
        $this->assertSame('/gecis-sayfasi?ref=footer#bilgi', $nav->fresh()->url);
        $this->assertFalse($nav->fresh()->is_active);
        $this->assertSame('https://example.com/sayfa/gecis-sayfasi', $external->fresh()->url);
        $this->get('/sayfa/gecis-sayfasi')->assertStatus(301)->assertRedirect('/gecis-sayfasi');
        $page->update(['slug' => 'yeni-sayfa-adresi']);
        $this->get('/sayfa/gecis-sayfasi')->assertRedirect('/yeni-sayfa-adresi');
        $this->get('/gecis-sayfasi')->assertRedirect('/yeni-sayfa-adresi');
        SeoRedirect::where('old_url', '/sayfa/gecis-sayfasi')->delete();
        $page->update(['title' => 'Başlık değişti']);
        $this->get('/sayfa/gecis-sayfasi')->assertNotFound();
        $page->update(['slug' => 'son-sayfa-adresi']);
        $this->get('/sayfa/gecis-sayfasi')->assertNotFound();
        $this->get('/son-sayfa-adresi')->assertOk();
    }

    public function test_reserved_system_paths_and_invalid_slugs_cannot_be_created(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['admin', 'blog', 'iletisim', 'deneme', 'login', 'auth', 'api', 'livewire', 'storage', 'sayfa', 'foo/bar', 'Sayfa', '../foo'] as $slug) {
            Livewire::test(PageForm::class)->set('title', 'Çakışma')->set('slug', $slug)->call('save')->assertHasErrors('slug');
            $this->assertDatabaseMissing('pages', ['slug' => $slug]);
        }
        $page = Page::create(['title' => 'Serbest', 'slug' => 'serbest-sayfa']);
        try {
            $page->update(['slug' => 'blog']);
            $this->fail('System route should be reserved.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slug', $e->errors());
        }
        $this->assertSame('serbest-sayfa', $page->fresh()->slug);
    }

    public function test_about_opens_directly_and_admin_live_link_uses_saved_root_slug(): void
    {
        $about = Page::create(['title' => 'Hakkımızda', 'slug' => 'hakkimizda', 'content' => 'Gerçek sayfa içeriği']);
        $this->get('/hakkimizda')->assertOk()->assertSee('Gerçek sayfa içeriği');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(PageForm::class, ['id' => $about->id])->assertSee('href="'.url('/hakkimizda').'"', false)
            ->assertDontSee('/sayfa/hakkimizda')->set('slug', 'kurumumuz')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->get('/hakkimizda')->assertStatus(301)->assertRedirect('/kurumumuz');
        $this->get('/kurumumuz')->assertOk();
    }
}

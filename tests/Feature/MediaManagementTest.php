<?php

namespace Tests\Feature;

use App\Livewire\Admin\MediaManager;
use App\Models\{AdminRole, Advertisement, BlogPost, Page, Question, SiteSetting, User};
use App\Support\{AdminAccess, MediaLibrary};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MediaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function image(string $path, bool $recent = false): void
    {
        Storage::disk('public')->put($path, 'image');
        if (!$recent) touch(Storage::disk('public')->path($path), now()->subHours(2)->timestamp);
    }

    public function test_existing_files_are_listed_with_draft_content_and_cross_module_references(): void
    {
        foreach (['question-image/shared.png', 'blog-images/cover.webp', 'branding/logo.png', 'profile-images/avatar.jpg', 'editor-images/unused.png'] as $path) $this->image($path);
        $this->image('livewire-tmp/temporary.png');
        Storage::disk('public')->put('private.txt', 'not an image');
        $post = BlogPost::create(['title' => 'Taslak blog', 'slug' => 'taslak-blog', 'is_published' => false,
            'featured_image_path' => 'blog-images/cover.webp', 'content' => '<img src="https://old.example/storage/question-image/shared.png?size=2">']);
        $page = Page::create(['title' => 'Taslak sayfa', 'slug' => 'taslak-sayfa', 'status' => 'draft',
            'content' => '<img src="/storage/question-image%2Fshared.png">']);
        $question = Question::create(['question_text' => '<p>Soru</p>', 'correct_option' => 'A', 'status' => 'Taslak',
            'options' => [['key' => 'A', 'text' => '<img src="/storage/question-image/shared.png">']]]);
        SiteSetting::query()->delete();
        SiteSetting::create(['site_name' => 'Test sitesi', 'logo' => 'branding/logo.png']);
        auth()->user()->forceFill(['avatar_path' => 'profile-images/avatar.jpg'])->save();
        Advertisement::create(['title' => 'Pasif reklam', 'location' => 'Header', 'device' => 'desktop', 'is_active' => false,
            'code' => '<div style="background-image: url(/storage/blog-images/cover.webp)"></div>']);
        $files = app(MediaLibrary::class)->files()->keyBy('path');
        $this->assertCount(5, $files);
        $this->assertCount(3, $files['question-image/shared.png']['uses']);
        $this->assertCount(2, $files['blog-images/cover.webp']['uses']);
        $this->assertCount(1, $files['branding/logo.png']['uses']);
        $this->assertCount(1, $files['profile-images/avatar.jpg']['uses']);
        $this->assertSame([], $files['editor-images/unused.png']['uses']);
        $this->assertContains(route('admin.pages.edit', $page->id), array_column($files['question-image/shared.png']['uses'], 'url'));
        $this->get(route('admin.media'))->assertOk()->assertSee('Medya Yönetimi')->assertSee('Taslak sayfa');
        Livewire::test(MediaManager::class)->set('filter', 'unused')->assertSee('unused.png')->assertDontSee('cover.webp')
            ->set('filter', 'used')->set('search', 'Taslak sayfa')->assertSee('shared.png')->assertDontSee('cover.webp');
    }

    public function test_deletion_rechecks_usage_after_confirmation_and_requires_an_unused_safe_file(): void
    {
        $this->image('question-image/remove.png');
        $screen = Livewire::test(MediaManager::class)->call('confirmDelete', 'question-image/remove.png');
        $page = Page::create(['title' => 'Yeni kullanım', 'slug' => 'yeni-kullanim', 'content' => '<img src="/storage/question-image/remove.png">']);
        $screen->call('delete')->assertHasErrors('media');
        Storage::disk('public')->assertExists('question-image/remove.png');
        $page->update(['content' => 'Görsel kaldırıldı']);
        $screen->call('delete')->assertHasNoErrors()->assertSet('pendingDelete', null)->assertSee('Kullanılmayan görsel silindi.');
        Storage::disk('public')->assertMissing('question-image/remove.png');
        $this->assertDatabaseHas('audit_logs', ['record_type' => 'Medya', 'record_title' => 'question-image/remove.png']);
        foreach (['../private.png', '/absolute.png', 'question-image/../../secret.png', 'question-image\\other.png', 'private.txt', 'livewire-tmp/image.png'] as $path) {
            $this->assertFalse(app(MediaLibrary::class)->safeFile($path));
            try { app(MediaLibrary::class)->deleteUnused($path); $this->fail('Unsafe path accepted'); }
            catch (ValidationException $e) { $this->assertArrayHasKey('media', $e->errors()); }
        }
    }

    public function test_recent_uploads_are_protected_and_partial_filename_matches_do_not_block_unused_files(): void
    {
        $this->image('question-image/new.png', true);
        Livewire::test(MediaManager::class)->call('confirmDelete', 'question-image/new.png')->call('delete')->assertHasErrors('media');
        Storage::disk('public')->assertExists('question-image/new.png');
        $this->image('question-image/test.png');
        Page::create(['title' => 'Benzer dosyalar', 'slug' => 'benzer-dosyalar',
            'content' => '<img src="/storage/question-image/other-test.png"><img src="/storage/question-image/test.png.bak">']);
        $this->assertSame([], app(MediaLibrary::class)->usageIndex(['question-image/test.png']));
        app(MediaLibrary::class)->deleteUnused('question-image/test.png');
        Storage::disk('public')->assertMissing('question-image/test.png');
    }

    public function test_automatic_cover_cleanup_preserves_references_in_other_content(): void
    {
        $this->image('blog-images/shared.png');
        $post = BlogPost::create(['title' => 'Kapak', 'slug' => 'kapak', 'featured_image_path' => 'blog-images/shared.png']);
        $page = Page::create(['title' => 'Ortak kapak', 'slug' => 'ortak-kapak', 'content' => '<img src="/storage/blog-images/shared.png">']);
        $post->delete();
        Storage::disk('public')->assertExists('blog-images/shared.png');
        $page->update(['content' => '']);
        app(MediaLibrary::class)->cleanup('blog-images/shared.png');
        Storage::disk('public')->assertMissing('blog-images/shared.png');
    }

    public function test_media_permission_is_required_and_editors_do_not_gain_it_automatically(): void
    {
        $this->assertNotContains('media', AdminAccess::defaultEditorPermissions());
        $editor = User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']);
        $role = AdminRole::updateOrCreate(['key' => 'editor'], ['name' => 'Editör', 'permissions' => []]);
        $this->actingAs($editor)->get(route('admin.media'))->assertForbidden();
        Livewire::test(MediaManager::class)->assertForbidden();
        $role->update(['permissions' => ['media']]);
        $this->get(route('admin.media'))->assertOk();
        $this->image('question-image/protected.png');
        $screen = Livewire::test(MediaManager::class)->call('confirmDelete', 'question-image/protected.png');
        $role->update(['permissions' => []]);
        $screen->call('delete')->assertForbidden();
        Storage::disk('public')->assertExists('question-image/protected.png');
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get(route('admin.media'))->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\BlogPostForm;
use App\Livewire\Admin\QuestionImportForm;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Models\QuestionImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditorAndAuthorTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_editor_stays_with_creator_while_last_editor_tracks_changes(): void
    {
        $creator = $this->editor();
        $this->actingAs($creator);
        $category = BlogCategory::create(['name' => 'Editör kontrolü', 'slug' => 'editor-kontrolu']);
        Livewire::test(BlogPostForm::class)->set('blog_category_id', $category->id)
            ->set('title', 'Otomatik editör')->set('slug', 'otomatik-editor')->set('content', '<p>İlk metin</p>')
            ->call('save')->assertHasNoErrors();
        $post = BlogPost::where('slug', 'otomatik-editor')->firstOrFail();
        $this->assertEquals($creator->id, $post->created_by);
        $this->assertSame($creator->name, $post->author_name);
        $this->assertNull($post->last_edited_by);
        $editor = User::factory()->create(['is_admin' => true, 'name' => 'Ali Düzenleyen']);
        $this->actingAs($editor);
        $this->travel(1)->days();
        Livewire::test(BlogPostForm::class, ['id' => $post->id])->assertSee('Yazar')
            ->assertDontSee('wire:model="author_name"', false)->assertSee($creator->name)
            ->set('content', '<p>Düzeltilen metin</p>')->call('save')->assertHasNoErrors()->assertNoRedirect()
            ->assertSee($creator->name)->assertSee('Son Düzenleyen')->assertSee($editor->name);
        $post->refresh();
        $this->assertSame($creator->name, $post->author_name);
        $this->assertEquals($creator->id, $post->created_by);
        $this->assertEquals($editor->id, $post->last_edited_by);
        $date = $post->content_updated_at;
        $this->travel(1)->days();
        $post->update(['author_name' => 'Sahte Editör']);
        $this->assertSame($creator->name, $post->fresh()->author_name);
        $this->assertTrue($post->fresh()->content_updated_at->equalTo($date));
        $article = $this->get(route('blog.show', $post))->assertOk()
            ->assertSee('Yazar Hakkında')->assertSee($creator->name)->assertSee($post->author_url);
        $this->assertSame(1, substr_count($article->getContent(), e($creator->name)));
        $creator->update(['biography' => 'Trafik eğitimi hakkında yazıyor.']);
        $this->get(route('blog.show', $post))->assertOk()
            ->assertSee('Yazar Hakkında')->assertSee('Trafik eğitimi hakkında yazıyor.');
        $this->get('/admin/blog-posts')->assertOk()->assertSee('Yazar')->assertSee($creator->name);
    }

    public function test_blog_editor_cannot_be_changed_through_livewire_input(): void
    {
        $this->actingAs($this->editor());
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        Livewire::test(BlogPostForm::class)->set('author_name', 'Sahte Editör');
    }

    private function editor(): User
    {
        return User::factory()->create(['name' => 'Mehmet AKTAŞ', 'email' => 'editor@example.test', 'is_admin' => true, 'role_name' => 'Editör']);
    }

    public function test_author_profile_uses_the_requested_author_and_dynamic_initials(): void
    {
        $post = BlogPost::create(['title' => 'Mehmet yazısı', 'slug' => 'mehmet-yazisi', 'author_name' => 'Mehmet AKTAŞ']);
        BlogPost::create(['title' => 'Diğer yazı', 'slug' => 'diger-yazi', 'author_name' => 'Başka Yazar']);
        BlogPost::create(['title' => 'Taslak yazı', 'slug' => 'taslak-yazi', 'author_name' => 'Mehmet AKTAŞ', 'is_published' => false]);
        $this->get('/yazar/mehmet-aktas')->assertOk()->assertSee('Mehmet AKTAŞ')->assertSee('MA')
            ->assertSee('Mehmet yazısı')->assertDontSee('Diğer yazı')->assertDontSee('Taslak yazı');
        $this->get(route('blog.show', $post))->assertOk()->assertSee('MA')->assertSee('/yazar/mehmet-aktas');
        $this->get('/yazar/olmayan-yazar')->assertNotFound();
    }

    public function test_editor_can_manage_content_and_is_sent_to_the_blog_list(): void
    {
        $this->actingAs($this->editor());
        $this->get('/admin')->assertRedirect(route('admin.blog-posts'));
        $this->get('/admin/blog-posts')->assertOk()->assertDontSee('Rol ve İzinler')->assertDontSee('Site Ayarları');
        foreach (['questions', 'topics', 'pages', 'blog-categories', 'traffic-signs'] as $module) {
            $this->get('/admin/'.$module)->assertOk();
        }
        $category = BlogCategory::create(['name' => 'Blog', 'slug' => 'blog']);
        Livewire::test(BlogPostForm::class)->assertSet('author_name', 'Mehmet AKTAŞ')
            ->set('blog_category_id', $category->id)->set('title', 'Editör yazısı')->set('slug', 'editor-yazisi')
            ->set('content', '<p>Editör içeriği</p>')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('blog_posts', ['slug' => 'editor-yazisi', 'author_name' => 'Mehmet AKTAŞ']);
    }

    public function test_editor_cannot_access_account_or_system_management(): void
    {
        $this->actingAs($this->editor());
        foreach (['users', 'users/create', 'staff-users', 'admin-roles', 'settings', 'integrations', 'audit-logs', 'contact-messages', 'reports', 'notification-campaigns'] as $module) {
            $this->get('/admin/'.$module)->assertForbidden();
        }
    }

    public function test_editor_cannot_replay_an_old_user_management_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role_name' => 'Yönetici']);
        $target = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/users')->assertOk();
        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $snapshot = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        $admin->update(['role_name' => 'Editör']);
        $this->postJson('/livewire/update', ['components' => [[
            'snapshot' => $snapshot, 'updates' => [],
            'calls' => [['path' => '', 'method' => 'delete', 'params' => [$target->id]]],
        ]]], ['X-Livewire' => 'true'])->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_administrator_keeps_full_access(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role_name' => 'Yönetici']));
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/settings')->assertOk();
    }

    public function test_editing_an_import_preserves_its_original_creator(): void
    {
        $editor = $this->editor();
        $import = QuestionImport::create(['file_name' => 'sorular.xlsx', 'user_id' => $editor->id, 'status' => 'Tamamlandı']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(QuestionImportForm::class, ['id' => $import->id])
            ->set('file_name', 'sorular-guncel.xlsx')->call('save')->assertHasNoErrors();
        $this->assertEquals($editor->id, $import->fresh()->user_id);
    }
}

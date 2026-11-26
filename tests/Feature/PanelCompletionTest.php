<?php
namespace Tests\Feature;
use App\Models\{User,Page,BlogPost,BlogCategory,Lecture,LectureCategory,SeoRedirect,SeoNotFoundLog,AuditLog,SiteSetting,Integration};
use App\Livewire\Admin\{PageForm,Settings,BlogPosts,BlogPostForm,SeoRedirects,SeoRedirectForm,IntegrationForm};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
class PanelCompletionTest extends TestCase {
    use RefreshDatabase;
    private function admin(): User { $u=User::factory()->create(['is_admin'=>true,'role_name'=>'Yönetici']); $this->actingAs($u);return $u; }
    private function article(string $slug='old',array $extra=[]): BlogPost {
        $category=BlogCategory::firstOrCreate(['slug'=>'review-blog'],['name'=>'Review Blog']);
        return BlogPost::create(array_replace(['blog_category_id'=>$category->id,'title'=>'Review '.$slug,'slug'=>$slug,'content'=>'Review article body',
            'author_name'=>'Review Author','is_published'=>true,'published_at'=>now()->subHour()],$extra));
    }
    public function test_404_paths_are_counted_without_query_secrets_or_protected_routes(): void {
        $this->get('/missing-review?token=secret')->assertNotFound();
        $this->call('HEAD','/missing-review?email=private')->assertNotFound();
        $row=SeoNotFoundLog::where('url','/missing-review')->firstOrFail();
        $this->assertSame(2,$row->request_count);$this->assertNotNull($row->first_seen_at);$this->assertNotNull($row->last_seen_at);
        $this->get('/admin/no-such-route');$this->postJson('/post-missing');
        $this->assertDatabaseCount('seo_not_found_logs',1);
        SeoRedirect::create(['old_url'=>'/gone','new_url'=>'','type'=>'410','status'=>'Aktif']);
        $this->get('/gone')->assertGone();$this->assertDatabaseCount('seo_not_found_logs',1);
    }
    public function test_audit_tracks_real_staff_changes_but_not_reads_or_secrets(): void {
        $u=$this->admin();
        $page=Page::create(['title'=>'Audit page','slug'=>'audit-page','status'=>'published','content'=>'Body']);
        $this->assertDatabaseHas('audit_logs',['user_id'=>$u->id,'record_type'=>'Sayfa','changed_fields'=>'Eklendi']);
        $count=AuditLog::count();
        Livewire::test(PageForm::class,['id'=>$page->id])->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertSame($count,AuditLog::count(),AuditLog::pluck('changed_fields')->toJson());
        Livewire::test(PageForm::class,['id'=>$page->id])->set('title','Edited page')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('audit_logs',['record_type'=>'Sayfa','changed_fields'=>'Düzenlendi: Başlık']);
        $this->get('/audit-page')->assertOk();
        $this->assertSame($count+1,AuditLog::count());
        $integration=Integration::where('integration_key','google_login')->firstOrFail();
        Livewire::test(IntegrationForm::class,['id'=>$integration->id])->set('fields.client_id','private-id')
            ->set('fields.client_secret','never-log-this-secret')->call('save')->assertHasNoErrors();
        $this->assertStringNotContainsString('never-log-this-secret',AuditLog::all()->toJson());
        $this->assertStringNotContainsString('private-id',AuditLog::all()->toJson());
        $page->delete();$this->assertDatabaseHas('audit_logs',['record_type'=>'Sayfa','changed_fields'=>'Silindi']);
        $count=AuditLog::count();auth()->logout();
        User::factory()->create();$this->assertSame($count,AuditLog::count());
    }
    public function test_settings_are_visible_immediately_and_after_a_second_save(): void {
        $this->admin();
        $form=Livewire::test(Settings::class)->set('site_name','Yeni Platform')->set('contact_email','hello@example.com')
            ->set('phone','0212 000 00 00')->set('address','Yeni Adres')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->get('/iletisim')->assertOk()->assertSee('Yeni Platform')->assertSee('hello@example.com')->assertSee('0212 000 00 00')->assertSee('Yeni Adres');
        $form->set('site_name','İkinci Platform')->set('contact_email','second@example.com')->call('save')->assertHasNoErrors();
        $this->get('/iletisim')->assertSee('İkinci Platform')->assertSee('second@example.com')->assertDontSee('hello@example.com');
        $form->set('phone', '')->call('save')->assertHasNoErrors();
        $this->get('/iletisim')->assertOk()->assertDontSee('0212 000 00 00')
            ->assertDontSee('>Telefon</small>', false)->assertDontSee('Çalışma Saatleri')->assertDontSee('09:00 - 18:00');
    }
    public function test_audit_keeps_the_actor_name_when_the_actor_account_is_deleted(): void {
        $actor=$this->admin();
        $actor->delete();
        $this->assertDatabaseHas('audit_logs',[
            'user_id'=>null,'user_name'=>$actor->name,'record_type'=>'Kullanıcı',
            'record_title'=>'#'.$actor->id,'changed_fields'=>'Silindi',
        ]);
    }
    public function test_slug_history_is_direct_deletable_and_not_recreated_by_later_edits(): void {
        $this->admin();$post=$this->article();
        $post->update(['slug'=>'second']);
        $this->get('/blog/old')->assertStatus(301)->assertRedirect('/blog/second');
        $post->update(['slug'=>'third']);
        $this->get('/blog/old')->assertRedirect('/blog/third');$this->get('/blog/second')->assertRedirect('/blog/third');
        $rule=SeoRedirect::where('old_url','/blog/old')->firstOrFail();
        Livewire::test(SeoRedirects::class)->call('delete',$rule->id)->assertHasNoErrors();
        $post->update(['title'=>'Changed without slug']);
        $this->get('/blog/old')->assertNotFound();
        $post->update(['slug'=>'fourth']);$this->get('/blog/old')->assertNotFound();
        $this->get('/blog/third')->assertRedirect('/blog/fourth');
        $post->update(['slug'=>'second']);
        $this->get('/blog/second')->assertOk();$this->get('/blog/fourth')->assertRedirect('/blog/second');
    }
    public function test_manual_redirects_are_preserved_and_slug_conflicts_roll_back(): void {
        $post=$this->article();
        SeoRedirect::create(['old_url'=>'/blog/old','new_url'=>'/blog','type'=>'302','status'=>'Aktif']);
        $post->update(['slug'=>'new']);$this->get('/blog/old')->assertStatus(302)->assertRedirect('/blog');
        SeoRedirect::create(['old_url'=>'/blog/blocked','new_url'=>'/blog','type'=>'301','status'=>'Aktif']);
        try { $post->update(['slug'=>'blocked']);$this->fail('Expected conflict'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('slug',$e->errors()); }
        $this->assertSame('new',$post->fresh()->slug);
        $this->assertDatabaseMissing('seo_redirects',['old_url'=>'/blog/new']);
    }
    public function test_category_changes_redirect_child_lecture_paths_and_moves(): void {
        $cat=LectureCategory::create(['name'=>'Old category','slug'=>'old-category']);
        $lecture=Lecture::create(['lecture_category_id'=>$cat->id,'title'=>'Lesson','slug'=>'lesson','content'=>'Lesson body','is_published'=>true]);
        $cat->update(['slug'=>'new-category']);
        $this->get('/konu/old-category')->assertRedirect('/konu/new-category');
        $this->get('/konu/old-category/lesson')->assertRedirect('/konu/new-category/lesson');
        $lecture->update(['slug'=>'new-lesson']);
        $this->get('/konu/old-category/lesson')->assertRedirect('/konu/new-category/new-lesson');
        $this->get('/konu/new-category/new-lesson')->assertOk();
        $blog=BlogCategory::create(['name'=>'Old blog','slug'=>'old-blog']);$blog->update(['slug'=>'new-blog']);
        $this->get('/blog?category=old-blog')->assertNotFound();
        $this->get('/kategori/old-blog')->assertStatus(301)->assertRedirect('/kategori/new-blog');
        SeoRedirect::where('old_url','/kategori/old-blog')->delete();
        $this->get('/kategori/old-blog')->assertNotFound();
    }
    public function test_scheduled_posts_wait_until_due_across_all_public_surfaces(): void {
        $this->travelTo(now()->startOfMinute());$due=now()->addHour();
        $post=$this->article('scheduled',['title'=>'Future Secret Title','author_name'=>'Future Writer','published_at'=>$due]);
        $live=$this->article('live');
        $this->assertNull($post->first_published_at);
        $this->get('/blog')->assertDontSee('Future Secret Title');
        $this->get('/blog/scheduled')->assertNotFound();
        $this->get('/blog/live')->assertDontSee('Future Secret Title');
        $this->get('/yazar/future-writer')->assertNotFound();
        $this->admin();
        Livewire::test(BlogPosts::class)->set('filterStatus','scheduled')->assertSee('Future Secret Title')->assertSee('Planlandı');
        Livewire::test(BlogPostForm::class,['id'=>$post->id])->assertDontSee('Canlı Blog Bağlantısı')->assertSee('Planlanan Yayın');
        $this->travelTo($due);auth()->logout();
        $this->get('/blog')->assertSee('Future Secret Title');$this->get('/blog/scheduled')->assertOk();
        $this->get('/yazar/future-writer')->assertOk()->assertSee('Future Secret Title');
        $this->assertTrue($post->fresh()->first_published_at->equalTo($due));
        $post->update(['is_published'=>false]);$this->get('/blog/scheduled')->assertNotFound();
    }
    public function test_editing_blog_publication_date_preserves_first_publication_and_content_update_history(): void {
        $this->travelTo(now()->startOfMinute());
        $this->admin();
        $post=$this->article('publication-date');
        $first=$post->first_published_at;
        $date=now()->subDays(5)->setTime(10,35);
        Livewire::test(BlogPostForm::class,['id'=>$post->id])->set('published_at',$date->format('Y-m-d\TH:i'))
            ->call('save')->assertHasNoErrors()->assertNoRedirect()->assertSee('İlk Yayınlanma')->assertSee($first->format('d.m.Y'));
        $post->refresh();
        $this->assertTrue($post->published_at->equalTo($date));
        $this->assertTrue($post->first_published_at->equalTo($first));
        $this->assertTrue($post->content_updated_at->equalTo(now()));
        $this->assertEquals(auth()->id(), $post->last_edited_by);
        $this->get('/admin/blog-posts')->assertOk()->assertSee($first->format('d.m.Y'));
        Livewire::test(BlogPostForm::class,['id'=>$post->id])->assertSet('published_at',$date->format('Y-m-d\TH:i'));
        $this->get('/blog/publication-date')->assertOk()->assertSee($date->format('d.m.Y'));
        $html=\Illuminate\Support\Facades\Blade::render('<x-admin.record-date :record="$record" />',['record'=>$post]);
        $this->assertStringContainsString($first->format('d.m.Y'),$html);
        $this->assertStringNotContainsString($date->format('d.m.Y'),$html);
        $this->travel(1)->days();
        $post->update(['content'=>'Gerçek içerik değişikliği']);
        $html=\Illuminate\Support\Facades\Blade::render('<x-admin.record-date :record="$record" />',['record'=>$post->fresh()]);
        $this->assertStringContainsString('Son Güncelleme',$html);
        $this->assertStringContainsString('İlk yayın: '.$first->format('d.m.Y'),$html);
        Livewire::test(BlogPostForm::class,['id'=>$post->id])
            ->assertSee('İlk Yayınlanma')->assertSee($first->format('d.m.Y'))
            ->assertSee('Son Güncelleme')->assertSee(now()->format('d.m.Y'));
    }
}

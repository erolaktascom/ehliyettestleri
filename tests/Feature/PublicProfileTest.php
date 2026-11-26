<?php
namespace Tests\Feature;

use App\Livewire\Admin\{SeoSettings, StaffUserForm, UserForm};
use App\Models\{User, BlogPost, SeoSetting, SeoRouteSetting};
use App\Support\{SeoTemplates, SiteSitemap, SeoPageCatalog};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Livewire\Livewire;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_a_namesake_does_not_take_over_an_existing_legacy_author_address(): void
    {
        $post = BlogPost::create(['title' => 'Eski yazı', 'slug' => 'eski-yazi', 'author_name' => 'Eski Yazar']);
        $url = $post->author_url;
        $member = User::factory()->create(['name' => 'Eski Yazar']);
        $this->assertNotSame('eski-yazar', $member->profile_slug);
        $this->assertSame($url, $post->fresh()->author_url);
        $this->get($url)->assertOk()->assertSee('Eski yazı');
        $this->get('/uye/'.$member->profile_slug)->assertOk()->assertDontSee('Eski yazı');
    }

    public function test_member_addresses_are_unique_stable_and_do_not_expose_private_account_data(): void
    {
        $member = User::factory()->create(['name' => 'Erol Üye', 'email' => 'private-email@example.com', 'phone' => '05550009999', 'biography' => 'Herkese açık kısa bilgi', 'birth_date' => '1990-01-09', 'city' => 'Özel Şehir']);
        $other = User::factory()->create(['name' => 'Erol Üye']);
        $this->assertNotSame($member->profile_slug, $other->profile_slug);
        $slug = $member->profile_slug;
        $member->update(['name' => 'Yeni İsim']);
        $this->assertSame($slug, $member->fresh()->profile_slug);
        $this->get('/uye/'.$slug)->assertOk()->assertSee('Yeni İsim')->assertSee('Herkese açık kısa bilgi')
            ->assertDontSee($member->email)->assertDontSee($member->phone)->assertDontSee('1990-01-09')->assertDontSee('Özel Şehir')
            ->assertDontSee('/yazar/'.$slug)->assertDontSee('Sınav Sonuçları');
        $this->get('/uye/olmayan')->assertNotFound();
        $staff = User::factory()->create(['is_admin' => true]);
        $this->get('/uye/'.$staff->profile_slug)->assertNotFound();
        $this->get('/yazar/'.$member->profile_slug)->assertNotFound();
    }

    public function test_author_posts_are_owned_by_account_not_name_and_profiles_do_not_cross_link(): void
    {
        $author = User::factory()->create(['name' => 'Aynı Yazar', 'is_admin' => true, 'biography' => '<script>alert(1)</script> Gerçek biyografi']);
        $other = User::factory()->create(['name' => 'Aynı Yazar', 'is_admin' => true]);
        $this->actingAs($author);
        $post = BlogPost::create(['title' => 'İlk Yazarın Yazısı', 'slug' => 'ilk-yazi', 'content' => 'İçerik', 'published_at' => now()]);
        BlogPost::create(['title' => 'Taslak Gizli', 'slug' => 'taslak-gizli', 'is_published' => false]);
        BlogPost::create(['title' => 'Gelecek Gizli', 'slug' => 'gelecek-gizli', 'published_at' => now()->addDay()]);
        $this->actingAs($other);
        BlogPost::create(['title' => 'İkinci Yazarın Yazısı', 'slug' => 'ikinci-yazi']);
        auth()->logout();
        $this->get('/yazar/'.$author->profile_slug)->assertOk()->assertSee('İlk Yazarın Yazısı')
            ->assertDontSee('İkinci Yazarın Yazısı')->assertDontSee('Taslak Gizli')->assertDontSee('Gelecek Gizli')
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('Gerçek biyografi')->assertDontSee('/uye/'.$author->profile_slug);
        $this->get($post->author_url)->assertOk();
        $this->get('/yazar/'.$other->profile_slug)->assertOk()->assertSee('İkinci Yazarın Yazısı')->assertDontSee('İlk Yazarın Yazısı');
        $empty = User::factory()->create(['is_admin' => true]);
        $this->get('/yazar/'.$empty->profile_slug)->assertOk()->assertSee('Henüz yayımlanmış bir yazı bulunmuyor.');
    }

    public function test_profile_seo_uses_only_separate_shared_member_and_author_rules(): void
    {
        $member = User::factory()->create(['name' => 'Örnek Üye']);
        $author = User::factory()->create(['name' => 'Örnek Yazar', 'is_admin' => true]);
        $rules = SeoTemplates::values();
        $rules['members'] = ['title' => '%ad% | Üye | %site_adi%', 'description' => '%kullanici_adi% üye profili', 'index' => 'noindex'];
        $rules['authors'] = ['title' => '%ad% | Yazar', 'description' => '%ad% yazıları', 'index' => 'index'];
        config(['app.url' => 'https://example.com']);
        SeoSetting::create(['canonical_base' => 'https://example.com', 'content_rules' => $rules]);
        SeoRouteSetting::create(['route_key' => 'members.show', 'seo_title' => 'Üyeye Özel Olmamalı', 'seo_description' => 'Özel', 'seo_index' => 'index']);
        $this->get('/uye/'.$member->profile_slug)->assertOk()->assertSee('<title>Örnek Üye | Üye | EhliyetGo</title>', false)
            ->assertSee('content="'.$member->profile_slug.' üye profili"', false)->assertSee('content="noindex, follow"', false)
            ->assertSee('href="https://example.com/uye/'.$member->profile_slug.'"', false)->assertDontSee('Üyeye Özel Olmamalı');
        $this->get('/yazar/'.$author->profile_slug)->assertOk()->assertSee('<title>Örnek Yazar | Yazar</title>', false);
        SeoRouteSetting::create(['route_key' => 'blog.author:'.$author->profile_slug, 'seo_title' => 'Özel Yazar Başlığı', 'seo_index' => 'noindex']);
        $this->get('/yazar/'.$author->profile_slug)->assertOk()->assertSee('<title>Örnek Yazar | Yazar</title>', false)
            ->assertSee('content="Örnek Yazar yazıları"', false)->assertSee('content="index, follow"', false)->assertDontSee('Özel Yazar Başlığı');
        $secondAuthor = User::factory()->create(['name'=>'İkinci Yazar','is_admin'=>true]);
        $this->get('/yazar/'.$secondAuthor->profile_slug)->assertOk()->assertSee('<title>İkinci Yazar | Yazar</title>',false);
        $this->get('/yazarlar-sitemap.xml')->assertOk()->assertSee('/yazar/'.$author->profile_slug);
        $rules['authors']['index']='noindex'; SeoSetting::first()->update(['content_rules'=>$rules]);
        $this->get('/yazar/'.$secondAuthor->profile_slug)->assertOk()->assertSee('content="noindex, follow"',false);
        $this->get('/yazarlar-sitemap.xml')->assertNotFound();
        $migration = require database_path('migrations/2026_11_02_260000_use_shared_profile_seo.php');
        SeoRouteSetting::create(['route_key'=>'home','seo_title'=>'Koru']);
        $migration->up();
        $this->assertSame(['home'], SeoRouteSetting::pluck('route_key')->all());
        $this->assertSame($rules,SeoSetting::first()->content_rules);
        $choices = SeoPageCatalog::choices();
        $this->assertArrayNotHasKey('blog.author:'.$author->profile_slug, $choices);
        $this->assertArrayNotHasKey('members.show', $choices);
        $this->actingAs($author);
        Livewire::test(SeoSettings::class)->assertSee('Üye Profilleri')->assertSee('Yazar Profilleri')
            ->set('content_rules.members.title', '%ad% profili')->call('save')->assertHasNoErrors()->assertNoRedirect();
        Livewire::test(SeoSettings::class)->assertSet('content_rules.members.title', '%ad% profili');
    }

    public function test_sitemap_selection_and_general_noindex_follow_public_member_visibility(): void
    {
        $member = User::factory()->create();
        $staff = User::factory()->create(['is_admin' => true]);
        $rules = SeoTemplates::values();
        config(['app.url' => 'https://example.com']);
        $setting = SeoSetting::create(['canonical_base' => 'https://example.com', 'content_rules' => $rules, 'sitemap_sections' => ['uyeler']]);
        $this->get('/uyeler-sitemap.xml')->assertOk()->assertSee('/uye/'.$member->profile_slug)->assertDontSee('/uye/'.$staff->profile_slug);
        $this->get('/sitemap_index.xml')->assertOk()->assertSee('/uyeler-sitemap.xml')->assertDontSee('/yazarlar-sitemap.xml');
        $rules['members']['index'] = 'noindex'; $setting->update(['content_rules' => $rules]);
        $this->get('/uyeler-sitemap.xml')->assertNotFound();
        $this->get('/sitemap_index.xml')->assertOk()->assertDontSee('/uyeler-sitemap.xml');
        $setting->update(['sitemap_sections' => []]);
        $this->get('/uyeler-sitemap.xml')->assertNotFound();
    }

    public function test_member_profile_upload_edit_and_remove_are_owner_only_and_ignore_forged_fields(): void
    {
        Storage::fake('public');
        $member = User::factory()->create();
        $other = User::factory()->create(['biography' => 'Diğer üyeye ait']);
        $this->put('/settings/profile', [])->assertRedirect('/login');
        $data = ['name' => $member->name, 'email' => $member->email, 'biography' => 'Yeni biyografi', 'profile_photo' => UploadedFile::fake()->image('avatar.png'),
            'id' => $other->id, 'is_admin' => true, 'profile_slug' => 'sahte', 'avatar_path' => '../private.txt', 'seo_title' => 'Özel başlık'];
        $this->actingAs($member)->from('/settings/profile')->put('/settings/profile', $data)->assertSessionHasNoErrors()->assertRedirect('/settings/profile');
        $member->refresh();
        $this->assertFalse($member->is_admin);
        $this->assertNotSame('sahte', $member->profile_slug);
        $this->assertSame('Yeni biyografi', $member->biography);
        $this->assertSame('Diğer üyeye ait', $other->fresh()->biography);
        Storage::disk('public')->assertExists($member->avatar_path);
        $oldPath = $member->avatar_path;
        $this->get('/settings/profile')->assertOk()->assertSee('Profilimi Görüntüle')->assertSee('Yeni biyografi');
        $this->put('/settings/profile', ['name' => $member->name, 'email' => $member->email, 'remove_photo' => true])->assertSessionHasNoErrors();
        $this->assertNull($member->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($oldPath);
        $this->put('/settings/profile', ['name' => $member->name, 'email' => $member->email, 'profile_photo' => UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('profile_photo');
    }

    public function test_admin_member_and_staff_forms_save_and_reopen_public_biography_and_photos(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        foreach ([UserForm::class => false, StaffUserForm::class => true] as $class => $staff) {
            $user = User::factory()->create(['is_admin' => $staff]);
            Livewire::test($class, ['id' => $user->id])->set('biography', 'Kaydedilen profil bilgisi')
                ->set('profile_photo', UploadedFile::fake()->image('photo.jpg'))->call('save')->assertHasNoErrors()->assertNoRedirect();
            $this->assertSame('Kaydedilen profil bilgisi', $user->fresh()->biography);
            Storage::disk('public')->assertExists($user->fresh()->avatar_path);
            Livewire::test($class, ['id' => $user->id])->assertSet('biography', 'Kaydedilen profil bilgisi')->assertSee('Profili Görüntüle');
        }
    }
}
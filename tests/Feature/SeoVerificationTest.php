<?php

namespace Tests\Feature;

use App\Livewire\Admin\{SeoSettings, SeoVerification};
use App\Models\{SeoSetting, User};
use App\Support\SiteVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeoVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_custom_tags_save_reopen_and_render_and_retired_fields_are_absent(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $tags = ['baidu-site-verification' => 'baidu123', 'p:domain_verify' => 'pin123', 'norton-safeweb-site-verification' => 'norton123'];
        $html = implode("\n", array_map(fn ($name, $value) => '<meta name="'.$name.'" content="'.$value.'">', array_keys($tags), array_values($tags)));
        Livewire::test(SeoVerification::class)->assertDontSee('id="verification-baidu"', false)
            ->assertDontSee('id="verification-pinterest"', false)->assertDontSee('id="verification-norton"', false)
            ->set('custom_verification', $html)->call('save')->assertHasNoErrors();
        $this->assertSame($tags, SeoSetting::first()->verification_tags);
        Livewire::test(SeoVerification::class)->assertSet('custom_verification', $html)->call('save')->assertHasNoErrors();
        $response = $this->get('/blog')->assertOk();
        foreach ($tags as $name => $value) $response->assertSee('<meta name="'.$name.'" content="'.$value.'">', false);
    }

    public function test_verification_menu_and_editor_permission_follow_registry(): void
    {
        $editor = User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']);
        $this->actingAs($editor)->get('/admin/seo-settings/verification')->assertForbidden();
        $role = \App\Models\AdminRole::where('key', 'editor')->firstOrFail();
        $role->update(['permissions' => ['seo-verification']]);
        $this->get('/admin/seo-settings/verification')->assertOk()->assertSee(route('admin.seo-verification'), false);
        $this->get('/admin/seo-settings')->assertForbidden();
        $this->assertNotContains('seo-verification', \App\Support\AdminAccess::defaultEditorPermissions());
    }
    public function test_tabs_have_separate_forms_and_saves_do_not_overwrite_each_other(): void
    {
        $this->get('/admin/seo-settings/verification')->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $general = Livewire::test(SeoSettings::class)->assertDontSee('id="verification-google"', false);
        $verification = Livewire::test(SeoVerification::class)->assertDontSee('wire:model="site_title"', false);
        $verification->set('verification.google', 'new-token')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $general->set('content_rules.blog_posts.title', 'Yeni başlık')->call('save')->assertHasNoErrors();
        $this->assertSame('new-token', SeoSetting::first()->verification_tags['google-site-verification']);
        $verification->set('verification.bing', 'bing-token')->call('save')->assertHasNoErrors();
        $this->assertSame('Yeni başlık', SeoSetting::first()->content_rules['blog_posts']['title']);
        $this->get('/admin/seo-settings/verification')->assertOk()->assertSee('Doğrulama Ayarlarını Kaydet');
    }
    public function test_provider_ids_and_meta_tags_save_reopen_and_render_in_public_head(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $form = Livewire::test(SeoVerification::class);
        foreach (SiteVerification::PROVIDERS as $key => $provider) {
            $form->assertSet('verification.'.$key, '')
                ->set('verification.'.$key, $key === 'google' ? 'google-token_123' : '<meta content="'.$key.'-token_123" name="'.$provider['name'].'" />');
        }
        $form->set('custom_verification', '<meta name="facebook-domain-verification" content="facebook123">')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $reopened = Livewire::test(SeoVerification::class);
        foreach (SiteVerification::PROVIDERS as $key => $provider) {
            $reopened->assertSet('verification.'.$key, $key.'-token_123');
        }
        auth()->logout();
        foreach (['/', '/blog'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $head = substr($html, 0, strpos($html, '</head>'));
            foreach (SiteVerification::PROVIDERS as $key => $provider) {
                $this->assertSame(1, substr_count($head, '<meta name="'.$provider['name'].'" content="'.$key.'-token_123">'));
            }
            $this->assertStringContainsString('<meta name="facebook-domain-verification" content="facebook123">', $head);
        }
    }

    public function test_clearing_fields_removes_tags_without_changing_other_settings(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoSettings::class)->set('content_rules.blog_posts.title', 'Korunan başlık')->call('save')->assertHasNoErrors();
        $form = Livewire::test(SeoVerification::class)->set('verification.google', 'test123')
            ->set('custom_verification', '<meta name="example-verification" content="custom123">')->call('save')->assertHasNoErrors();
        $this->get('/blog')->assertOk()->assertSee('content="test123"', false);
        $form->set('verification.google', '')->set('custom_verification', '')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertSame([], SeoSetting::first()->verification_tags);
        $this->assertSame('Korunan başlık', SeoSetting::first()->content_rules['blog_posts']['title']);
        $this->get('/blog')->assertOk()->assertDontSee('name="google-site-verification"', false)->assertDontSee('name="example-verification"', false);
    }

    public function test_invalid_markup_wrong_provider_and_duplicate_tags_are_rejected_atomically(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoVerification::class)->set('verification.google', 'keep123')->call('save')->assertHasNoErrors();
        foreach ([
            ['verification.google', '<meta name="yandex-verification" content="wrong">'],
            ['verification.google', '<meta name="google-site-verification" content="abc" onload="alert(1)">'],
            ['verification.google', '<meta name="google-site-verification" content="abc"><script>alert(1)</script>'],
            ['custom_verification', '<meta http-equiv="refresh" content="0;url=https://example.com">'],
            ['custom_verification', '<meta name="robots" content="noindex">'],
            ['custom_verification', '<meta name="google-site-verification" content="duplicate">'],
            ['custom_verification', '<meta name="example-verify" content="abc"><meta name="example-verify" content="def">'],
            ['custom_verification', '<script>alert(1)</script>'],
            ['custom_verification', '<meta name="example-verify" content="&quot;><script>alert(1)</script>">'],
        ] as [$field, $value]) {
            Livewire::test(SeoVerification::class)->set($field, $value)->call('save')->assertHasErrors($field);
            $this->assertSame(['google-site-verification' => 'keep123'], SeoSetting::first()->verification_tags);
        }
    }

    public function test_stored_values_are_html_escaped_and_admin_does_not_publish_verification_tags(): void
    {
        SeoSetting::query()->delete();
        SeoSetting::create(['site_title' => 'Test', 'verification_tags' => ['example-verification' => '"><script id="injected">alert(1)</script>']]);
        $this->get('/')->assertOk()->assertDontSee('<script id="injected">', false)->assertSee('&lt;script', false);
        $this->get('/admin/seo-settings')->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin')->assertOk()->assertDontSee('name="example-verification"', false);
    }
}

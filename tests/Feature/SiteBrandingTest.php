<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\{SiteSetting, User, SeoSetting};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_text_social_links_and_copyright_are_saved_and_rendered_safely(): void
    {
        $this->admin();
        $form = Livewire::test(Settings::class)->set('site_name', 'Yeni Marka')
            ->set('site_description', 'Özel site açıklaması <script>alert(1)</script>')
            ->set('copyright_text', '© {year} {site_name} — Özel telif')
            ->set('social_links.instagram', '@example')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->get('/blog')->assertOk()->assertSee('https://www.instagram.com/example')
            ->assertSee('Özel site açıklaması &lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('© '.now()->year.' Yeni Marka — Özel telif')
            ->assertDontSee('href="https://tiktok.com"', false);
        $form->set('social_links.instagram', '')->set('site_description', '')->set('copyright_text', '')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->get('/blog')->assertDontSee('https://www.instagram.com/example')->assertDontSee('Özel telif');
        Livewire::test(Settings::class)->assertSet('copyright_text', '')->assertSet('social_links.instagram', '');
    }

    public function test_upload_replace_remove_and_frontend_admin_favicon_output(): void
    {
        Storage::fake('public');
        $this->admin();
        $form = Livewire::test(Settings::class)
            ->assertSee('src="'.asset('favicon.svg').'" alt="Mevcut Favicon"', false)
            ->set('logoUpload', UploadedFile::fake()->image('brand.png', 200, 80))
            ->set('faviconUpload', UploadedFile::fake()->image('icon.png', 32, 32))
            ->call('save')->assertHasNoErrors()->assertNoRedirect()->assertSet('logoUpload', null);
        $settings = SiteSetting::firstOrFail();
        $oldLogo = $settings->logo;
        Storage::disk('public')->assertExists([$settings->logo, $settings->favicon]);
        foreach (['logo' => 'Logo', 'favicon' => 'Favicon'] as $field => $label) {
            $form->assertSee('src="'.$settings->mediaUrl($field).'" alt="Mevcut '.$label.'"', false);
        }
        foreach (['/blog', '/admin', '/admin/login'] as $path) {
            if ($path === '/admin/login') auth()->logout();
            $this->get($path)->assertOk()->assertSee($settings->mediaUrl('logo'), false)->assertSee($settings->mediaUrl('favicon'), false);
        }
        $this->admin();
        $form->set('logoUpload', UploadedFile::fake()->image('new-brand.png', 120, 60))
            ->call('save')->assertHasNoErrors();
        Storage::disk('public')->assertMissing($oldLogo);
        $settings->refresh();
        $this->assertNotSame($oldLogo, $settings->logo);
        $newLogo = $settings->logo;
        $icon = $settings->favicon;
        $form->set('remove_logo', true)->set('remove_favicon', true)->call('save')->assertHasNoErrors();
        Storage::disk('public')->assertMissing([$newLogo, $icon]);
        $this->assertNull($settings->fresh()->logo);
        $this->assertNull($settings->fresh()->favicon);
        $this->get('/blog')->assertOk()->assertSee('favicon.svg');
        $form->assertSee('src="'.asset('favicon.svg').'" alt="Mevcut Favicon"', false);
    }

    public function test_invalid_uploads_and_social_urls_do_not_change_settings(): void
    {
        Storage::fake('public');
        $this->admin();
        foreach (['javascript:alert(1)', '//evil.test', '/local', 'https://example.com/%0aevil'] as $url) {
            Livewire::test(Settings::class)->set('social_links.youtube', $url)->call('save')->assertHasErrors('social_links.youtube');
        }
        Livewire::test(Settings::class)->set('logoUpload', UploadedFile::fake()->createWithContent('bad.svg', '<svg onload="alert(1)"></svg>'))
            ->call('save')->assertHasErrors('logoUpload');
        Livewire::test(Settings::class)->set('faviconUpload', UploadedFile::fake()->create('large.png', 513, 'image/png'))
            ->call('save')->assertHasErrors('faviconUpload');
        $this->assertSame([], Storage::disk('public')->allFiles('branding'));
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']));
        $this->get('/admin/settings')->assertForbidden();
    }

    public function test_usernames_build_each_profile_url_and_are_normalized_on_save(): void
    {
        $this->admin();
        $form = Livewire::test(Settings::class);
        foreach (SiteSetting::SOCIAL_PLATFORMS as $key => $label) $form->set('social_links.'.$key, ' @ehliyetgo ');
        $form->call('save')->assertHasNoErrors()->assertNoRedirect();
        $setting = SiteSetting::firstOrFail();
        foreach (SiteSetting::SOCIAL_BASES as $key => $base) {
            $this->assertSame('ehliyetgo', $setting->social_links[$key]);
            $this->assertSame($base.'ehliyetgo', $setting->socialUrl($key));
            $this->get('/blog')->assertSee($base.'ehliyetgo');
        }
        Livewire::test(Settings::class)->assertSet('social_links.youtube', 'ehliyetgo');
        foreach (['https://www.youtube.com/@valid', 'two words', '../other', '@@name', 'x?admin=true', ['invalid']] as $bad) {
            $form->set('social_links.youtube', $bad)->call('save')->assertHasErrors('social_links.youtube');
        }
        $this->assertSame('ehliyetgo', $setting->fresh()->social_links['youtube']);
    }

    public function test_legacy_profile_addresses_load_as_usernames_without_corrupting_channel_urls(): void
    {
        $setting = SiteSetting::create(['site_name' => 'Test Site', 'contact_email' => 'test@example.com']);
        $setting->update(['social_links' => [
            'instagram' => 'https://www.instagram.com/old.name/?source=web',
            'youtube' => 'https://www.youtube.com/@old-channel',
            'x' => 'https://twitter.com/old_name',
        ]]);
        $this->admin();
        Livewire::test(Settings::class)->assertSet('social_links.instagram', 'old.name')
            ->assertSet('social_links.youtube', 'old-channel')->assertSet('social_links.x', 'old_name')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('old.name', $setting->fresh()->social_links['instagram']);
        $legacy = SiteSetting::make(['social_links' => ['youtube' => 'https://www.youtube.com/channel/UClegacy']]);
        $this->assertSame('https://www.youtube.com/channel/UClegacy', $legacy->socialUrl('youtube'));
    }
}

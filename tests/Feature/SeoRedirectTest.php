<?php

namespace Tests\Feature;

use App\Livewire\Admin\SeoRedirectForm;
use App\Livewire\Admin\SeoRedirects;
use App\Models\{SeoRedirect, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeoRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Test each rule independently of redirects seeded by page URL migrations.
        SeoRedirect::query()->delete();
    }

    private function rule(string $old, string $new, string $type = '301', string $status = 'Aktif'): SeoRedirect
    {
        return SeoRedirect::create(['old_url' => $old, 'new_url' => $new, 'type' => $type, 'status' => $status]);
    }

    public function test_rules_are_not_cached_and_admin_deletion_immediately_restores_404(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['301', '302', '410'] as $type) {
            $path = '/deleted-rule-'.$type;
            $rule = $this->rule($path, '/blog', $type);
            foreach (['GET', 'HEAD'] as $method) {
                $response = $this->call($method, $path)->assertStatus((int) $type);
                $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
                $this->assertTrue($response->headers->hasCacheControlDirective('no-cache'));
            }
            Livewire::test(SeoRedirects::class)->call('delete', $rule->id)->assertHasNoErrors();
            $this->assertModelMissing($rule);
            $this->get($path)->assertNotFound()->assertHeaderMissing('Location');
            $this->call('HEAD', $path)->assertNotFound()->assertHeaderMissing('Location');
        }
    }

    public function test_redirect_admin_page_clears_legacy_http_cache_without_clearing_login(): void
    {
        $this->get('/admin/seo-redirects')->assertRedirect(route('admin.login'))
            ->assertHeaderMissing('Clear-Site-Data');
        $user = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($user)->get('/admin/seo-redirects')->assertOk()
            ->assertHeader('Clear-Site-Data', '"cache"');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertHeaderMissing('Clear-Site-Data');
    }

    public function test_active_301_and_302_work_before_route_matching_and_update_usage(): void
    {
        foreach (['301', '302'] as $type) {
            $rule = $this->rule('/old-'.$type, '/blog', $type);
            $this->get('/old-'.$type)->assertStatus((int) $type)->assertRedirect('/blog');
            $this->assertSame(1, $rule->fresh()->usage_count);
            $this->assertNotNull($rule->fresh()->last_used_at);
            $this->call('HEAD', '/old-'.$type)->assertStatus((int) $type)->assertRedirect('/blog');
            $this->assertSame(2, $rule->fresh()->usage_count);
        }
    }

    public function test_410_returns_gone_without_location_and_counts_get_and_head(): void
    {
        $rule = $this->rule('/removed-page', '', '410');
        $this->get('/removed-page?utm_source=test')->assertStatus(410)->assertHeaderMissing('Location')
            ->assertSee('Bu sayfa kaldırıldı.')->assertHeader('X-Robots-Tag', 'noindex');
        $this->assertSame(1, $rule->fresh()->usage_count);
        $this->assertNotNull($rule->fresh()->last_used_at);
        $this->call('HEAD', '/removed-page')->assertStatus(410)->assertHeaderMissing('Location')->assertContent('');
        $this->assertSame(2, $rule->fresh()->usage_count);
        $this->rule('/blog', '', '410');
        $this->get('/blog')->assertStatus(410);
    }

    public function test_disabled_410_post_and_protected_routes_are_not_blocked(): void
    {
        $inactive = $this->rule('/disabled-gone', '', '410', 'Pasif');
        $post = $this->rule('/post-gone', '', '410');
        $admin = $this->rule('/admin', '', '410');
        $this->get('/disabled-gone')->assertNotFound();
        $this->post('/post-gone')->assertStatus(405)->assertHeaderMissing('Location');
        $this->get('/admin')->assertRedirect(route('admin.login'));
        foreach ([$inactive, $post, $admin] as $rule) $this->assertSame(0, $rule->fresh()->usage_count);
    }

    public function test_redirect_chain_can_end_at_a_410_rule(): void
    {
        $this->rule('/old-page', '/removed-page');
        $gone = $this->rule('/removed-page', '/ignored-destination', '410');
        $this->get('/old-page')->assertStatus(301)->assertRedirect('/removed-page');
        $this->assertSame(0, $gone->fresh()->usage_count);
        $this->get('/removed-page')->assertStatus(410)->assertHeaderMissing('Location');
    }

    public function test_410_can_be_saved_without_target_and_switching_types_restores_target_validation(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoRedirectForm::class)->set('type', '410')->set('sources.0.url', '/removed-page')
            ->assertDontSee('Hedef Adres')->call('save')->assertHasNoErrors();
        $rule = SeoRedirect::firstOrFail();
        $this->assertSame('', $rule->new_url);
        Livewire::test(SeoRedirectForm::class, ['id' => $rule->id])->assertSet('type', '410')
            ->set('type', '302')->assertSee('Hedef Adres')->call('save')->assertHasErrors('new_url')
            ->set('new_url', '/blog')->call('save')->assertHasNoErrors();
        $this->get('/removed-page')->assertStatus(302)->assertRedirect('/blog');
        Livewire::test(SeoRedirectForm::class, ['id' => $rule->id])->set('type', '410')->call('save')->assertHasNoErrors();
        $this->assertSame('', $rule->fresh()->new_url);
        $this->get('/removed-page')->assertStatus(410)->assertHeaderMissing('Location');
        Livewire::test(SeoRedirectForm::class)->set('type', '410')->set('sources.0.url', '/removed-page')->call('save')->assertHasErrors('sources.0.url');
    }

    public function test_existing_pages_redirect_but_inactive_and_non_get_rules_do_not_run(): void
    {
        $this->rule('/blog', '/deneme');
        $inactive = $this->rule('/inactive-rule', '/blog', '301', 'Pasif');
        $post = $this->rule('/post-rule', '/blog');
        $this->get('/blog')->assertStatus(301)->assertRedirect('/deneme');
        $this->get('/inactive-rule')->assertNotFound();
        $this->post('/post-rule')->assertStatus(405)->assertHeaderMissing('Location');
        $this->assertSame(0, $inactive->fresh()->usage_count);
        $this->assertSame(0, $post->fresh()->usage_count);
    }

    public function test_query_matching_normalization_and_external_targets(): void
    {
        $this->rule('/old', '/blog?category=new#articles');
        $this->get('/old/?utm_source=test&category=old&q=hello%20world')->assertStatus(301)
            ->assertRedirect('/blog?category=new&q=hello%20world&utm_source=test#articles');
        $this->rule('/old?category=special', '/deneme', '302');
        $this->get('/old?category=special')->assertStatus(302)->assertRedirect('/deneme?category=special');
        $this->rule(url('/full-url'), '/blog');
        $this->get('/full-url')->assertRedirect('/blog');
        $this->rule('/external', 'https://example.org/page?from=site', '302');
        $this->get('/external?private=value')->assertStatus(302)->assertRedirect('https://example.org/page?from=site');
        $this->get('/old/child')->assertNotFound();
    }

    public function test_bad_legacy_rows_and_cycles_do_not_redirect_or_increment_usage(): void
    {
        $rules = [
            $this->rule('/cycle-one', '/cycle-two'), $this->rule('/cycle-two', '/cycle-one'),
            $this->rule('/self', '/self'), $this->rule('/invalid', 'javascript:alert(1)'),
            $this->rule('/bad-status', '/blog', '999'),
        ];
        foreach (['/cycle-one', '/cycle-two', '/self', '/invalid', '/bad-status'] as $url) $this->get($url)->assertNotFound();
        foreach ($rules as $rule) $this->assertSame(0, $rule->fresh()->usage_count);
        $this->rule('/chain-one', '/chain-two');
        $this->rule('/chain-two', '/blog', '302');
        $this->get('/chain-one')->assertStatus(301)->assertRedirect('/chain-two');
        $this->get('/chain-two')->assertStatus(302)->assertRedirect('/blog');
    }

    public function test_admin_and_livewire_routes_are_not_redirected_even_by_legacy_rules(): void
    {
        $admin = $this->rule('/admin', 'https://example.org');
        $livewire = $this->rule('/livewire/update', 'https://example.org');
        $login = $this->rule('/login', 'https://example.org');
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/login')->assertOk();
        $this->post('/livewire/update', ['components' => []])->assertHeaderMissing('Location');
        foreach ([$admin, $livewire, $login] as $rule) $this->assertSame(0, $rule->fresh()->usage_count);
    }

    public function test_admin_can_create_edit_disable_and_reenable_a_working_rule(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoRedirectForm::class)->set('sources.0.url', url('/editable/'))->set('new_url', '/blog')->call('save')->assertHasNoErrors();
        $rule = SeoRedirect::firstOrFail();
        $this->assertSame('/editable', $rule->old_url);
        $this->get('/editable')->assertStatus(301)->assertRedirect('/blog');
        Livewire::test(SeoRedirectForm::class, ['id' => $rule->id])->set('new_url', '/deneme')->set('type', '302')->call('save')->assertHasNoErrors();
        $this->get('/editable')->assertStatus(302)->assertRedirect('/deneme');
        Livewire::test(SeoRedirectForm::class, ['id' => $rule->id])->set('status', 'Pasif')->call('save')->assertHasNoErrors();
        $this->get('/editable')->assertNotFound();
        Livewire::test(SeoRedirectForm::class, ['id' => $rule->id])->set('status', 'Aktif')->call('save')->assertHasNoErrors();
        $this->get('/editable')->assertStatus(302);
    }

    public function test_form_rejects_duplicates_cycles_protected_sources_and_unsafe_targets(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->rule('/taken', '/blog');
        $this->rule('/chain-a', '/chain-b');
        foreach ([
            ['/taken/', '/deneme', 'sources.0.url'], ['/chain-b', '/chain-a', 'new_url'],
            ['/same', '/same?utm=a', 'new_url'], ['/admin', '/blog', 'sources.0.url'],
            ['https://example.org/foreign', '/blog', 'sources.0.url'], ['/old#hash', '/blog', 'sources.0.url'],
            ['/unsafe', 'javascript:alert(1)', 'new_url'], ['/unsafe', '//example.org', 'new_url'],
            ['/unsafe', '/%2fexample.org', 'new_url'], ['/unsafe', '/blog%0d%0aLocation:x', 'new_url'],
            ['/unsafe', '/a/../admin', 'new_url'],
        ] as [$old, $new, $field]) {
            Livewire::test(SeoRedirectForm::class)->set('sources.0.url', $old)->set('new_url', $new)->call('save')->assertHasErrors($field);
        }
        $this->assertSame(2, SeoRedirect::count());
    }
}

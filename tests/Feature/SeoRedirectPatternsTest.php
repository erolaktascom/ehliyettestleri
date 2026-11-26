<?php

namespace Tests\Feature;

use App\Livewire\Admin\{SeoRedirectForm, SeoRedirects};
use App\Models\{SeoRedirect, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeoRedirectPatternsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Test each rule independently of redirects seeded by page URL migrations.
        SeoRedirect::query()->delete();
    }

    private function source(string $url, string $type = 'exact', bool $ignore = false): array
    {
        return ['url' => $url, 'match_type' => $type, 'ignore_case' => $ignore];
    }

    private function rule(array $sources, string $target = '/destination', string $type = '301', string $status = 'Aktif'): SeoRedirect
    {
        return SeoRedirect::create(['old_url' => $sources[0]['url'], 'sources' => $sources, 'new_url' => $target, 'type' => $type, 'status' => $status]);
    }

    public function test_multiple_sources_can_be_saved_reopened_searched_and_removed(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoRedirectForm::class)->set('sources.0.url', '/old-one')->call('addSource')
            ->set('sources.1.url', '/old-two')->set('new_url', '/destination')->call('save')->assertHasNoErrors();
        $rule = SeoRedirect::firstOrFail();
        foreach (['/old-one', '/old-two'] as $url) $this->get($url)->assertRedirect('/destination');
        $this->assertSame(2, $rule->fresh()->usage_count);
        Livewire::test(SeoRedirects::class)->set('search', 'old-two')->assertSee('/old-one')->assertSee('/old-two');
        Livewire::test(SeoRedirectForm::class, ['id' => $rule->id])->assertSet('sources.1.url', '/old-two')
            ->call('removeSource', 0)->call('save')->assertHasNoErrors();
        $this->assertSame('/old-two', $rule->fresh()->old_url);
        $this->assertCount(1, $rule->fresh()->sources);
        $this->get('/old-one')->assertNotFound();
        $this->get('/old-two')->assertRedirect('/destination');
    }

    public function test_partial_matches_use_only_path_and_respect_case_setting(): void
    {
        foreach ([['contains', 'legacy', '/a/legacy/b', '/a/LEGACY/b'], ['starts_with', '/start-', '/start-page', '/x/start-page'], ['ends_with', '.html', '/page.html', '/page.html/child']] as [$type, $pattern, $yes, $no]) {
            $rule = $this->rule([$this->source($pattern, $type)]);
            $this->get($yes)->assertRedirect('/destination');
            $this->get($no)->assertNotFound();
            $this->get('/unmatched?value='.urlencode($pattern))->assertNotFound();
            $rule->delete();
        }
        $rule = $this->rule([$this->source('/CASE', 'exact', true)]);
        $this->get('/case')->assertRedirect('/destination');
        $rule->update(['sources' => [$this->source('/CASE', 'exact', false)]]);
        $this->get('/case')->assertNotFound();
    }

    public function test_regex_capture_groups_are_expanded_and_query_values_are_encoded(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoRedirectForm::class)->set('sources', [$this->source('^/old/(.*)$', 'regex', true)])
            ->set('new_url', '/new/$1')->call('save')->assertHasNoErrors();
        $this->get('/OLD/category/item?ref=test')->assertRedirect('/new/category/item?ref=test');
        SeoRedirect::first()->update(['new_url' => '/search?q=$1']);
        $this->get('/old/a%26admin%3D1')->assertRedirect('/search?q=a%26admin%3D1');
    }

    public function test_exact_rules_take_priority_over_patterns_and_query_rules_over_plain_paths(): void
    {
        $this->rule([$this->source('/old', 'starts_with')], '/broad');
        $this->rule([$this->source('/old-page')], '/specific');
        $this->rule([$this->source('/old-page?x=1')], '/query');
        $this->get('/old-page')->assertRedirect('/specific');
        $this->get('/old-page?x=1')->assertRedirect('/query?x=1');
        $this->get('/old-other')->assertRedirect('/broad');
    }

    public function test_form_rejects_invalid_regex_duplicates_unsupported_statuses_and_empty_sources(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoRedirectForm::class)->set('sources', [$this->source('(', 'regex')])->set('new_url', '/destination')
            ->call('save')->assertHasErrors('sources.0.url');
        Livewire::test(SeoRedirectForm::class)->set('sources', [$this->source('/same'), $this->source('/SAME', 'exact', true)])
            ->set('new_url', '/destination')->call('save')->assertHasErrors('sources.1.url');
        $this->rule([$this->source('/existing', 'contains', true)]);
        Livewire::test(SeoRedirectForm::class)->set('sources', [$this->source('/EXISTING', 'contains')])->set('new_url', '/destination')
            ->call('save')->assertHasErrors('sources.0.url');
        foreach (['307', '451'] as $type) {
            Livewire::test(SeoRedirectForm::class)->set('sources.0.url', '/valid')->set('new_url', '/destination')->set('type', $type)
                ->call('save')->assertHasErrors('type');
        }
        Livewire::test(SeoRedirectForm::class)->set('sources', [])->set('new_url', '/destination')->call('save')->assertHasErrors('sources');
        Livewire::test(SeoRedirectForm::class)->call('removeSource', 0)->assertSet('sources', [$this->source('')]);
        Livewire::test(SeoRedirectForm::class)->set('sources', array_fill(0, 21, $this->source('/old')))
            ->set('new_url', '/destination')->call('save')->assertHasErrors('sources');
    }

    public function test_pattern_cycles_are_rejected_and_unsafe_existing_rules_fail_closed(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SeoRedirectForm::class)->set('sources', [$this->source('/loop', 'starts_with')])
            ->set('new_url', '/loop-again')->call('save')->assertHasErrors('new_url');
        $loop = $this->rule([$this->source('^/loop/(.*)$', 'regex')], '/loop/again/$1');
        $this->get('/loop/a')->assertNotFound();
        $this->assertSame(0, $loop->fresh()->usage_count);
        $bad = $this->rule([$this->source('(', 'regex')]);
        $this->get('/bad-regex')->assertNotFound();
        $this->assertSame(0, $bad->fresh()->usage_count);
        $slow = $this->rule([$this->source('^/(a+)+$', 'regex')]);
        $this->get('/'.str_repeat('a', 200).'!')->assertNotFound();
        $this->assertSame(0, $slow->fresh()->usage_count);
    }

    public function test_grouped_410_patterns_and_protected_routes(): void
    {
        $gone = $this->rule([$this->source('/removed', 'starts_with'), $this->source('.gone', 'ends_with')], '', '410');
        $this->get('/removed-page')->assertStatus(410)->assertHeaderMissing('Location');
        $this->get('/page.gone')->assertStatus(410)->assertHeaderMissing('Location');
        $gone->update(['status' => 'Pasif']);
        $this->get('/removed-page')->assertNotFound();
        $broad = $this->rule([$this->source('.*', 'regex')], '', '410');
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/login')->assertOk();
        $this->post('/livewire/update', ['components' => []])->assertHeaderMissing('Location');
        $this->assertSame(0, $broad->fresh()->usage_count);
    }

    public function test_legacy_source_loads_as_exact_and_form_has_only_requested_types(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $rule = SeoRedirect::create(['old_url' => '/legacy', 'new_url' => '/destination', 'type' => '301', 'status' => 'Aktif']);
        Livewire::test(SeoRedirectForm::class, ['id' => $rule->id])->assertSet('sources', [$this->source('/legacy')])
            ->assertSee('Tam eşleşme')->assertSee('İçerir')->assertSee('Şununla başlar')->assertSee('Şununla biter')->assertSee('Düzenli ifade')
            ->assertSee('301 Kalıcı Yönlendirme')->assertSee('302 Geçici Yönlendirme')->assertSee('410 İçerik Kaldırıldı')
            ->assertDontSee('value="307"', false)->assertDontSee('value="451"', false);
        $this->get('/legacy')->assertRedirect('/destination');
    }
}

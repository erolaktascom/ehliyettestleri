<?php
namespace Tests\Feature;
use App\Models\{Advertisement, User};
use App\Livewire\Admin\{AdvertisementForm, Advertisements};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdvertisementDevicesTest extends TestCase
{
    use RefreshDatabase;
    private function ad(string $device, string $location, string $title): Advertisement {
        return Advertisement::create(['device' => $device, 'location' => $location, 'title' => $title, 'is_active' => true, 'code' => '<img src="/ads/test.png">']);
    }
    public function test_404_omits_all_ad_code_and_spacing_while_normal_pages_keep_unlabelled_ads(): void {
        Advertisement::query()->delete();
        foreach ([['desktop', 'Header'], ['mobile', 'Header'], ['desktop', 'LeftBanner'], ['desktop', 'RightBanner']] as [$device, $location]) {
            $this->ad($device, $location, 'AD_MARKER_'.$device.$location);
        }
        foreach (['/missing-ad-free-page', '/deneme/missing-exam'] as $path) {
            $this->get($path)->assertNotFound()->assertDontSee('data-ad-slot=', false)
                ->assertDontSee('AD_MARKER_')->assertDontSee('has-side-ads has-left-ad', false)
                ->assertSee('Ana Sayfaya Dön')->assertSee('Alt bilgi sütunları');
        }
        $this->get('/blog')->assertOk()->assertSee('data-ad-slot="desktop.Header"', false)
            ->assertSee('AD_MARKER_desktopHeader')->assertDontSee('>Reklam</p>', false);
    }
    public function test_routes_forms_and_device_specific_locations(): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['desktop', 'mobile'] as $device) {
            $ad = $this->ad($device, 'Header', $device.'-header');
            $this->get(route('admin.advertisements.'.$device))->assertOk()->assertSee($ad->title);
            $this->get(route('admin.advertisements.'.$device.'.create'))->assertOk();
            $this->get(route('admin.advertisements.'.$device.'.edit', $ad->id))->assertOk();
        }
        Livewire::test(AdvertisementForm::class, ['device' => 'mobile'])->assertSee('Üst Kısım (Header)')->assertDontSee('Sol Banner')->assertDontSee('Sağ Banner')
            ->set('title', 'forged-side')->set('location', 'LeftBanner')->call('save')->assertHasErrors('location');
        $desktop = Advertisement::where('device', 'desktop')->first();
        $this->get(route('admin.advertisements.mobile.edit', $desktop->id))->assertNotFound();
    }
    public function test_create_edit_and_search_are_isolated_between_devices(): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $desktop = $this->ad('desktop', 'Header', 'DESKTOP_RECORD');
        Livewire::test(AdvertisementForm::class, ['device' => 'mobile'])->set('title', 'MOBILE_RECORD')->set('code', '<img src="/mobile.png">')->call('save')
            ->assertHasNoErrors()->assertRedirect(route('admin.advertisements.mobile'));
        $mobile = Advertisement::where('device', 'mobile')->firstOrFail();
        Livewire::test(AdvertisementForm::class, ['device' => 'mobile', 'id' => $mobile->id])->set('is_active', false)->call('save')->assertHasNoErrors();
        $this->assertTrue($desktop->fresh()->is_active); $this->assertFalse($mobile->fresh()->is_active);
        Livewire::test(Advertisements::class, ['device' => 'mobile'])->set('search', 'Header')->assertSee('MOBILE_RECORD')->assertDontSee('DESKTOP_RECORD');
        Livewire::test(Advertisements::class, ['device' => 'mobile'])->call('delete', $mobile->id);
        $this->assertModelExists($desktop); $this->assertModelMissing($mobile);
    }
    public function test_site_has_four_independent_slots_and_only_eligible_ad_is_selected(): void {
        Advertisement::query()->delete();
        $this->ad('desktop', 'Header', 'DESKTOP_HEADER');
        $this->ad('mobile', 'Header', 'MOBILE_HEADER');
        $left = $this->ad('desktop', 'LeftBanner', 'LEFT_SIDE');
        $this->ad('desktop', 'RightBanner', 'RIGHT_SIDE');
        $this->ad('mobile', 'LeftBanner', 'INVALID_MOBILE_SIDE');
        $html = $this->get('/blog')->assertOk()->getContent();
        foreach (['desktop.Header', 'mobile.Header', 'desktop.LeftBanner', 'desktop.RightBanner'] as $slot) $this->assertStringContainsString('data-ad-slot="'.$slot.'"', $html);
        $this->assertStringNotContainsString('INVALID_MOBILE_SIDE', $html);
        $this->assertStringContainsString('min-width: 1024px', $html);
        $this->assertStringContainsString('max-width: 1023px', $html);
        $this->assertStringContainsString('<template x-if="visible">', $html);
        $left->update(['is_active' => false]);
        $this->get('/blog')->assertDontSee('LEFT_SIDE');
        $this->travel(1)->minutes();
        $this->ad('desktop', 'Header', 'LATEST_HEADER');
        $this->get('/blog')->assertSee('LATEST_HEADER')->assertDontSee('DESKTOP_HEADER')->assertSee('MOBILE_HEADER');
    }
    public function test_code_is_isolated_and_empty_ads_are_hidden(): void {
        Advertisement::query()->delete();
        $ad = $this->ad('desktop', 'Header', 'Script ad');
        $ad->update(['code' => '<script>window.adTest = true;</script>']);
        $this->get('/blog')->assertSee('sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"', false)
            ->assertDontSee('<script>window.adTest = true;</script>', false);
        $ad->update(['code' => '']);
        $this->get('/blog')->assertDontSee('javascript:alert(1)', false)->assertDontSee('data-ad-slot=', false);
    }
    public function test_existing_header_is_copied_without_losing_content(): void {
        Advertisement::query()->delete();
        $migration = require database_path('migrations/2026_10_29_100000_separate_advertisement_devices.php');
        $migration->down();
        \Illuminate\Support\Facades\Schema::table('advertisements', fn (\Illuminate\Database\Schema\Blueprint $table) => $table->string('image')->nullable());
        DB::table('advertisements')->insert(['title' => 'Existing', 'location' => 'header', 'code' => 'existing-code', 'is_active' => false, 'image' => '/existing.png']);
        $migration->up();
        $ads = Advertisement::where('title', 'Existing')->get();
        $this->assertCount(2, $ads);
        $this->assertEqualsCanonicalizing(['desktop', 'mobile'], $ads->pluck('device')->all());
        foreach ($ads as $ad) {
            $this->assertSame('Header', $ad->location); $this->assertFalse($ad->is_active);
            $this->assertSame('existing-code', $ad->code); $this->assertSame('/existing.png', $ad->image);
        }
    }
    public function test_new_subroutes_keep_existing_advertisement_permission(): void {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role_name' => 'Editör']));
        foreach (['desktop', 'mobile'] as $device) {
            $ad = $this->ad($device, 'Header', 'Protected');
            $this->get(route('admin.advertisements.'.$device))->assertForbidden();
            $this->get(route('admin.advertisements.'.$device.'.create'))->assertForbidden();
            $this->get(route('admin.advertisements.'.$device.'.edit', $ad->id))->assertForbidden();
        }
    }
}

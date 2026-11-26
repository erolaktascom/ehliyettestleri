<?php

namespace Tests\Feature;

use App\Livewire\Admin\{AdvertisementForm, Advertisements};
use App\Models\{Advertisement, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdvertisementAudienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_member_ads_are_selected_on_server_for_each_device_and_slot(): void
    {
        Advertisement::query()->delete();
        foreach (['desktop' => ['Header', 'LeftBanner', 'RightBanner'], 'mobile' => ['Header']] as $device => $locations) {
            foreach ($locations as $location) {
                foreach (['members', 'guests', 'everyone'] as $audience) {
                    Advertisement::create(['title' => $device.'-'.$location.'-'.$audience.'-AD', 'device' => $device,
                        'location' => $location, 'audience' => [$audience], 'is_active' => true, 'code' => '<img src="/ads/'.$audience.'.png">']);
                }
            }
        }
        $guest = $this->get('/blog')->assertOk();
        foreach (['desktop-Header', 'desktop-LeftBanner', 'desktop-RightBanner', 'mobile-Header'] as $slot) {
            $guest->assertSee($slot.'-guests-AD')->assertDontSee($slot.'-members-AD')->assertDontSee($slot.'-everyone-AD');
        }
        $this->actingAs(User::factory()->create());
        $member = $this->get('/blog')->assertOk();
        foreach (['desktop-Header', 'desktop-LeftBanner', 'desktop-RightBanner', 'mobile-Header'] as $slot) {
            $member->assertSee($slot.'-members-AD')->assertDontSee($slot.'-guests-AD')->assertDontSee($slot.'-everyone-AD');
        }
        Advertisement::whereJsonContains('audience', 'members')->update(['is_active' => false]);
        $this->get('/blog')->assertSee('desktop-Header-everyone-AD')->assertDontSee('desktop-Header-guests-AD');
        Advertisement::whereJsonContains('audience', 'everyone')->delete();
        $this->get('/blog')->assertDontSee('data-ad-slot=', false)->assertDontSee('has-side-ads has-left-ad', false);
        auth()->logout();
        $this->get('/blog')->assertSee('desktop-Header-guests-AD')->assertDontSee('desktop-Header-members-AD');
    }

    public function test_audience_is_saved_loaded_validated_and_filterable_independently_of_device(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['desktop', 'mobile'] as $device) {
            Livewire::test(AdvertisementForm::class, ['device' => $device])->assertSet('audience', ['everyone'])
                ->set('title', $device.'-MEMBER_AD')->set('code', '<img src="/ad.png">')->set('audience', ['members'])->call('save')->assertHasNoErrors();
            $ad = Advertisement::where('title', $device.'-MEMBER_AD')->firstOrFail();
            $this->assertSame(['members'], $ad->audience);
            Livewire::test(AdvertisementForm::class, ['device' => $device, 'id' => $ad->id])->assertSet('audience', ['members'])
                ->set('audience', ['invalid'])->call('save')->assertHasErrors('audience.0');
            $this->assertSame(['members'], $ad->fresh()->audience);
            Livewire::test(AdvertisementForm::class, ['device' => $device, 'id' => $ad->id])->set('audience', ['guests'])
                ->call('save')->assertHasNoErrors();
            Livewire::test(AdvertisementForm::class, ['id' => $ad->id])->assertSet('audience', ['guests']);
            Livewire::test(Advertisements::class, ['device' => $device])->set('filterAudience', 'members')->assertDontSee($ad->title)
                ->set('filterAudience', 'guests')->assertSee($ad->title)->assertSee('Ziyaretçiler');
        }
        Livewire::test(Advertisements::class, ['device' => 'desktop'])->set('search', 'Header')->set('filterAudience', 'guests')
            ->assertSee('desktop-MEMBER_AD')->assertDontSee('mobile-MEMBER_AD');
    }

    public function test_legacy_audiences_keep_their_scope_after_migration(): void
    {
        Advertisement::query()->delete();
        foreach (['everyone', 'members', 'guests'] as $audience) {
            $ad = Advertisement::create(['title' => 'Legacy '.$audience, 'device' => 'desktop', 'location' => 'Header', 'code' => '<b>Legacy</b>', 'is_active' => true]);
            \Illuminate\Support\Facades\DB::table('advertisements')->where('id', $ad->id)->update(['audience' => $audience]);
        }
        $migration = require database_path('migrations/2026_10_30_200000_allow_multiple_ad_audiences.php');
        $migration->up();
        $migration->up();
        $this->assertSame(['everyone'], Advertisement::where('title', 'Legacy everyone')->sole()->audience);
        $this->assertSame(['members', 'premium'], Advertisement::where('title', 'Legacy members')->sole()->audience);
        $this->assertSame(['guests'], Advertisement::where('title', 'Legacy guests')->sole()->audience);
        $this->actingAs(User::factory()->create(['premium_until' => now()->addDay()]));
        $this->get('/blog')->assertDontSee('data-ad-slot=', false)->assertDontSee('Legacy members')->assertDontSee('Legacy everyone');
    }

    public function test_premium_and_standard_members_are_separate_and_expiry_changes_the_audience(): void
    {
        Advertisement::query()->delete();
        foreach (['desktop' => ['Header', 'LeftBanner', 'RightBanner'], 'mobile' => ['Header']] as $device => $locations) {
            foreach ($locations as $location) foreach (['members', 'premium', 'everyone'] as $key) {
                Advertisement::create(['title' => $device.$location.'-'.$key, 'device' => $device, 'location' => $location,
                    'audience' => [$key], 'is_active' => true, 'code' => '<b>'.$key.'</b>']);
            }
        }
        $premium = User::factory()->create(['premium_until' => now()->addHour()]);
        $this->actingAs($premium);
        $this->get('/blog')->assertDontSee('data-ad-slot=', false)->assertDontSee('<b>premium</b>', false)
            ->assertDontSee('<b>everyone</b>', false)->assertDontSee('<b>members</b>', false);
        $this->assertTrue(Advertisement::slots()->isEmpty());
        $this->travel(2)->hours();
        $this->get('/blog')->assertSee('desktopHeader-members')->assertSee('mobileHeader-members')->assertDontSee('desktopHeader-premium');
        auth()->logout();
        $this->get('/blog')->assertSee('desktopHeader-everyone')->assertDontSee('desktopHeader-members')->assertDontSee('desktopHeader-premium');
    }

    public function test_checkbox_groups_are_combined_persisted_and_filtered_and_everyone_is_exclusive(): void
    {
        Advertisement::query()->delete();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        foreach (['desktop', 'mobile'] as $device) {
            $form = Livewire::test(AdvertisementForm::class, ['device' => $device])->set('title', 'COMBINED_'.$device)->set('code', '<b>Combined</b>')
                ->call('toggleAudience', 'guests')->assertSet('audience', ['guests'])
                ->call('toggleAudience', 'members')->assertSet('audience', ['guests', 'members'])
                ->call('save')->assertHasNoErrors();
            $ad = Advertisement::where('title', 'COMBINED_'.$device)->sole();
            Livewire::test(AdvertisementForm::class, ['id' => $ad->id])->assertSet('audience', ['guests', 'members']);
            Livewire::test(Advertisements::class, ['device' => $device])->set('filterAudience', 'members')->assertSee($ad->title)
                ->set('filterAudience', 'premium')->assertDontSee($ad->title);
            $form->call('toggleAudience', 'everyone')->assertSet('audience', ['everyone'])
                ->call('toggleAudience', 'everyone')->assertSet('audience', [])->call('save')->assertHasErrors('audience');
        }
        $this->get('/blog')->assertSee('COMBINED_desktop');
        auth()->logout();
        $this->get('/blog')->assertSee('COMBINED_desktop')->assertSee('COMBINED_mobile');
        $this->actingAs(User::factory()->create(['premium_until' => now()->addMonth()]));
        $this->get('/blog')->assertDontSee('COMBINED_desktop')->assertDontSee('COMBINED_mobile');
        Advertisement::query()->update(['code' => '']);
        $this->get('/blog')->assertDontSee('data-ad-slot=', false);
    }
}

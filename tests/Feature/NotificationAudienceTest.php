<?php

namespace Tests\Feature;

use App\Livewire\Admin\NotificationCampaignForm;
use App\Models\{NotificationCampaign, User};
use App\Support\NotificationAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationAudienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_counts_standard_expired_and_premium_accounts_without_duplicates(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        User::factory()->create(['premium_until' => now()->subDay()]);
        User::factory()->create(['premium_until' => now()]);
        User::factory()->create(['premium_until' => now()->addDay()]);
        $form = Livewire::test(NotificationCampaignForm::class)->assertSet('status', 'Taslak')
            ->assertViewHas('recipientCount', 4)->assertSet('sent_at', null)
            ->call('toggleAudience', 'premium')->assertSet('target_groups', ['premium'])->assertViewHas('recipientCount', 1)
            ->call('toggleAudience', 'members')->assertViewHas('recipientCount', 4)
            ->call('toggleAudience', 'premium')->assertSet('target_groups', ['members'])->assertViewHas('recipientCount', 3)
            ->call('toggleAudience', 'members')->assertViewHas('recipientCount', 0)
            ->set('title', 'Duyuru')->call('save')->assertHasErrors('target_groups')
            ->call('toggleAudience', 'everyone')->assertSet('target_groups', ['everyone'])->assertViewHas('recipientCount', 4)
            ->call('toggleAudience', 'guests')->assertSet('target_groups', ['everyone']);
        $this->assertSame(0, NotificationAudience::query([])->count());
        $form->set('target_groups', ['guests'])->call('save')->assertHasErrors('target_groups.0');
        $this->assertSame(0, NotificationCampaign::count());
    }

    public function test_save_recalculates_count_and_edit_retains_groups_and_stays_on_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $form = Livewire::test(NotificationCampaignForm::class)->set('title', 'Test duyuru')
            ->set('target_groups', ['members', 'premium'])->assertViewHas('recipientCount', 1);
        User::factory()->create(['premium_until' => now()->addDay()]);
        $form->call('save')->assertHasNoErrors()->assertRedirect(route('admin.notification-campaigns'));
        $campaign = NotificationCampaign::sole();
        $this->assertSame(2, $campaign->recipient_count);
        $this->assertSame(['members', 'premium'], $campaign->target_groups);
        $this->assertSame('Üyeler, Premium Üyeler', $campaign->target);
        $this->assertNull($campaign->sent_at);
        Livewire::test(NotificationCampaignForm::class, ['id' => $campaign->id])
            ->assertSet('target_groups', ['members', 'premium'])->call('toggleAudience', 'everyone')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertSame(['everyone'], $campaign->fresh()->target_groups);
        $this->assertSame(1, NotificationCampaign::count());
    }

    public function test_legacy_targets_are_not_silently_broadened_or_historical_counts_changed_on_view(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $campaign = NotificationCampaign::create(['title' => 'Eski kampanya', 'type' => 'Duyuru', 'target' => 'Aktif Olmayanlar', 'status' => 'Planlandı', 'recipient_count' => 450]);
        Livewire::test(NotificationCampaignForm::class, ['id' => $campaign->id])->assertSet('target_groups', [])
            ->assertSee('Aktif Olmayanlar')->assertViewHas('recipientCount', 0)->call('save')->assertHasErrors('target_groups');
        $this->assertSame(450, $campaign->fresh()->recipient_count);
        $this->assertSame('Aktif Olmayanlar', $campaign->fresh()->target);
        $campaign->update(['target' => 'Tüm Üyeler']);
        Livewire::test(NotificationCampaignForm::class, ['id' => $campaign->id])->assertSet('target_groups', ['everyone'])
            ->assertViewHas('recipientCount', 1);
        $this->assertSame(450, $campaign->fresh()->recipient_count);
    }
}

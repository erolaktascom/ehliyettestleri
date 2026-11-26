<?php

namespace Tests\Feature;

use App\Livewire\Admin\{AutomationRuleForm, IntegrationForm, NotificationCampaignForm};
use App\Models\{AutomationRule, ExamAttempt, Integration, NotificationCampaign, NotificationDelivery, User};
use App\Services\{CampaignMailer, NotificationProcessor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB, Mail};
use Livewire\Livewire;
use Tests\TestCase;

class NotificationProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Mail::fake();
    }

    private function campaign(array $attributes = []): NotificationCampaign
    {
        return NotificationCampaign::create(array_merge([
            'title' => 'Duyuru {ad}', 'content' => 'Merhaba {ad}', 'target' => 'Herkes', 'target_groups' => ['everyone'],
            'is_in_app' => true, 'is_push' => false, 'is_email' => false, 'status' => 'Planlandı',
            'execution_enabled' => true, 'scheduled_at' => now()->addHour(),
        ], $attributes));
    }

    private function rule(array $attributes = []): AutomationRule
    {
        return AutomationRule::create(array_merge([
            'name' => 'Hoş geldin', 'trigger' => 'registered', 'is_active' => true, 'activated_at' => now(),
            'target_groups' => ['everyone'], 'delivery_channels' => ['in_app'], 'channels' => 'Site İçi Bildirim',
            'wait_hours' => 1, 'subject' => 'Merhaba {ad}', 'content' => 'Çalışmaya başlayalım.',
        ], $attributes));
    }

    public function test_scheduled_campaign_waits_selects_current_audience_and_sends_once(): void
    {
        User::factory()->create(['premium_until' => now()->addMinutes(30)]);
        $user = User::factory()->create(['name' => 'Ayşe', 'premium_until' => now()->addDay()]);
        $campaign = $this->campaign(['target_groups' => ['premium']]);
        app(NotificationProcessor::class)->run();
        $this->assertSame(0, NotificationDelivery::count());
        $this->travel(61)->minutes();
        app(NotificationProcessor::class)->run();
        app(NotificationProcessor::class)->run();
        $this->assertSame(1, NotificationDelivery::count());
        $delivery = NotificationDelivery::sole();
        $this->assertSame($user->id, $delivery->user_id);
        $this->assertSame('Duyuru Ayşe', $delivery->subject);
        $this->assertSame('sent', $delivery->status);
        $this->assertSame('Gönderildi', $campaign->fresh()->status);
        $this->assertSame(1, $campaign->fresh()->recipient_count);
        $this->assertNotNull($campaign->fresh()->sent_at);
        Mail::assertNothingSent();
    }

    public function test_drafts_old_records_and_unconfigured_rules_never_send(): void
    {
        User::factory()->create();
        $this->campaign(['execution_enabled' => false, 'scheduled_at' => now()->subDay()]);
        $this->campaign(['status' => 'Taslak', 'scheduled_at' => now()->subDay()]);
        $this->rule(['activated_at' => null]);
        $this->artisan('notifications:process')->assertSuccessful();
        $this->assertSame(0, NotificationDelivery::count());
        $this->assertNotNull(Cache::get('notifications.last_run_at'));
    }

    public function test_form_requires_future_schedule_content_channel_and_configured_email(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $form = Livewire::test(NotificationCampaignForm::class)->set('title', 'Duyuru')->set('status', 'Planlandı')
            ->call('save')->assertHasErrors(['scheduled_at', 'content'])
            ->set('content', 'Mesaj')->set('scheduled_at', now()->subHour()->format('Y-m-d\TH:i'))->call('save')->assertHasErrors('scheduled_at')
            ->set('scheduled_at', now()->addHour()->format('Y-m-d\TH:i'))->set('is_in_app', false)->call('save')->assertHasErrors('is_in_app')
            ->set('is_email', true)->call('save')->assertHasErrors('is_email')
            ->set('is_email', false)->set('is_in_app', true)->call('save')->assertHasNoErrors();
        $campaign = NotificationCampaign::sole();
        $this->assertTrue($campaign->execution_enabled);
        $this->assertSame('Planlandı', $campaign->status);
        $this->assertSame(now()->addHour()->format('Y-m-d H:i'), $campaign->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame(0, NotificationDelivery::count());
        Livewire::test(NotificationCampaignForm::class)->set('title', 'Fake')->set('status', 'Gönderildi')->call('save')->assertHasErrors('status');
    }

    public function test_partial_campaign_can_be_cancelled_without_resending_or_editing_sent_content(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        User::factory()->count(2)->create();
        $campaign = $this->campaign(['scheduled_at' => now()]);
        app(NotificationProcessor::class)->run(1);
        $this->assertSame(1, $campaign->deliveries()->where('status', 'sent')->count());
        Livewire::test(NotificationCampaignForm::class, ['id' => $campaign->id])->set('status', 'Taslak')->set('content', 'Değişti')
            ->call('save')->assertHasErrors('status')->call('cancelPending')->assertHasNoErrors('is_email');
        app(NotificationProcessor::class)->run();
        $this->assertSame('Merhaba {ad}', $campaign->fresh()->content);
        $this->assertSame('İptal', $campaign->fresh()->status);
        $this->assertSame(2, $campaign->deliveries()->where('status', 'cancelled')->count());
        $this->assertSame(1, $campaign->deliveries()->where('status', 'sent')->count());
    }

    public function test_mail_failure_is_not_counted_as_sent_and_preflight_failures_can_be_retried(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $campaign = $this->campaign(['scheduled_at' => now(), 'is_email' => true]);
        app(NotificationProcessor::class)->run();
        $this->assertSame('Hata', $campaign->fresh()->status);
        $this->assertNull($campaign->fresh()->sent_at);
        $this->assertSame(1, $campaign->deliveries()->where('status', 'sent')->count());
        $this->assertSame(1, $campaign->deliveries()->where('status', 'failed')->count());
        $this->mock(CampaignMailer::class, function ($mock) {
            $mock->shouldReceive('settings')->andReturn(['host' => 'test']);
            $mock->shouldReceive('send')->once();
        });
        Livewire::test(NotificationCampaignForm::class, ['id' => $campaign->id])->call('retryFailed')->assertHasNoErrors();
        app(NotificationProcessor::class)->run();
        app(NotificationProcessor::class)->run();
        $this->assertSame(2, $campaign->deliveries()->where('status', 'sent')->count());
        $this->assertSame('Gönderildi', $campaign->fresh()->status);
    }

    public function test_uncertain_smtp_result_is_never_automatically_retried_or_exposed(): void
    {
        User::factory()->create();
        $this->mock(CampaignMailer::class, function ($mock) {
            $mock->shouldReceive('settings')->andReturn(['host' => 'test']);
            $mock->shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp-password-secret'));
        });
        $campaign = $this->campaign(['scheduled_at' => now(), 'is_in_app' => false, 'is_email' => true]);
        app(NotificationProcessor::class)->run();
        app(NotificationProcessor::class)->run();
        $this->assertSame('uncertain', NotificationDelivery::sole()->status);
        $this->assertStringNotContainsString('smtp-password-secret', NotificationDelivery::sole()->error);
        $this->assertSame('Hata', $campaign->fresh()->status);
    }

    public function test_registration_rules_respect_activation_delay_exclude_staff_and_dedupe(): void
    {
        User::factory()->create(['created_at' => now()->subDay()]);
        $rule = $this->rule();
        $this->travel(1)->minutes();
        User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        app(NotificationProcessor::class)->run();
        $this->assertSame(0, NotificationDelivery::count());
        $this->travel(61)->minutes();
        app(NotificationProcessor::class)->run();
        app(NotificationProcessor::class)->run();
        $this->assertSame($user->id, NotificationDelivery::sole()->user_id);
        $this->assertNotNull($rule->fresh()->last_checked_at);
        $this->assertNotNull($rule->fresh()->last_triggered_at);
    }

    public function test_successful_exam_rules_ignore_failures_guests_and_old_attempts(): void
    {
        $user = User::factory()->create();
        $rule = $this->rule(['trigger' => 'exam_passed', 'wait_hours' => 0]);
        foreach ([['user_id' => $user->id, 'is_passed' => false], ['user_id' => null, 'is_passed' => true], ['user_id' => $user->id, 'is_passed' => true, 'completed_at' => now()->subDay()], ['user_id' => $user->id, 'is_passed' => true]] as $data) {
            ExamAttempt::create(array_merge(['started_at' => now(), 'expires_at' => now()->addHour(), 'completed_at' => now(), 'status' => 'completed', 'question_ids' => []], $data));
        }
        app(NotificationProcessor::class)->run();
        app(NotificationProcessor::class)->run();
        $this->assertSame($rule->id, NotificationDelivery::sole()->rule_id);
    }

    public function test_inactivity_is_once_per_visit_cycle_and_real_visits_update_activity(): void
    {
        $rule = $this->rule(['trigger' => 'inactive']);
        $user = User::factory()->create();
        $this->travel(2)->hours();
        app(NotificationProcessor::class)->run();
        app(NotificationProcessor::class)->run();
        $this->assertSame(1, NotificationDelivery::count());
        $this->actingAs($user)->get('/bildirimler')->assertOk();
        $this->assertSame(now()->format('Y-m-d H:i:s'), $user->fresh()->last_active_at->format('Y-m-d H:i:s'));
        app(NotificationProcessor::class)->run();
        $this->assertSame(1, NotificationDelivery::count());
        $this->travel(2)->hours();
        app(NotificationProcessor::class)->run();
        $this->assertSame(2, NotificationDelivery::count());
    }

    public function test_inbox_is_private_escapes_content_and_read_action_checks_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $campaign = $this->campaign(['scheduled_at' => now(), 'content' => '<script>alert(1)</script>']);
        app(NotificationProcessor::class)->run();
        $delivery = $campaign->deliveries()->where('user_id', $owner->id)->firstOrFail();
        $this->get('/bildirimler')->assertRedirect('/login');
        $this->actingAs($other)->post('/bildirimler/'.$delivery->id.'/okundu')->assertNotFound();
        $this->actingAs($owner)->get('/bildirimler')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post('/bildirimler/'.$delivery->id.'/okundu')->assertRedirect();
        $this->assertNotNull($delivery->fresh()->read_at);
    }

    public function test_rule_configuration_requires_message_and_last_check_is_not_save_time(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(AutomationRuleForm::class)->set('name', 'Yeni Kural')->set('is_active', true)
            ->call('save')->assertHasErrors(['subject', 'content'])
            ->set('subject', 'Merhaba {ad}')->set('content', 'Hoş geldin.')->call('save')->assertHasNoErrors();
        $rule = AutomationRule::sole();
        $this->assertNotNull($rule->activated_at);
        $this->assertNull($rule->last_checked_at);
        $this->assertNull($rule->last_triggered_at);
        Livewire::test(AutomationRuleForm::class, ['id' => $rule->id])->set('is_active', false)->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_smtp_settings_are_validated_encrypted_and_do_not_send_on_save(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $smtp = Integration::where('integration_key', 'smtp')->sole();
        $settings = ['host' => 'smtp.example.test', 'port' => '587', 'encryption' => 'tls', 'username' => 'mailer', 'password' => 'secret-test-only', 'from_address' => 'noreply@example.test'];
        Livewire::test(IntegrationForm::class, ['id' => $smtp->id])->set('fields', $settings)->set('is_active', true)
            ->set('fields.port', '99999')->call('save')->assertHasErrors('fields.port');
        $this->assertFalse($smtp->fresh()->is_active);
        Livewire::test(IntegrationForm::class, ['id' => $smtp->id])->set('fields', $settings)->set('is_active', true)
            ->call('save')->assertHasNoErrors()->assertSet('fields.password', '')->assertDontSee('secret-test-only');
        $this->assertStringNotContainsString('secret-test-only', DB::table('integrations')->where('id', $smtp->id)->value('configuration'));
        $this->assertNotNull(app(CampaignMailer::class)->settings());
        Mail::assertNothingSent();
    }

    public function test_smtp_transport_uses_panel_settings_and_plain_text_without_log_fallback(): void
    {
        $settings = ['host' => 'smtp.example.test', 'port' => '465', 'encryption' => 'ssl', 'username' => 'mailer', 'password' => 'test-secret', 'from_address' => 'noreply@example.test', 'from_name' => 'EhliyetGo'];
        Integration::where('integration_key', 'smtp')->firstOrFail()->update(['is_active' => true, 'configuration' => $settings]);
        \App\Models\SiteSetting::query()->delete();
        \App\Models\SiteSetting::create(['site_name' => 'Güncel Marka']);
        $delivery = new NotificationDelivery(['subject' => 'Başlık', 'content' => '<b>Metin</b>', 'recipient_email' => 'student@example.test']);
        $transport = \Mockery::mock(\Illuminate\Mail\Mailer::class);
        $transport->shouldReceive('raw')->once()->withArgs(function ($body, $callback) {
            $this->assertSame('<b>Metin</b>', $body);
            $email = new \Symfony\Component\Mime\Email;
            $callback(new \Illuminate\Mail\Message($email));
            $this->assertSame('student@example.test', $email->getTo()[0]->getAddress());
            $this->assertSame('noreply@example.test', $email->getFrom()[0]->getAddress());
            $this->assertSame('Güncel Marka', $email->getFrom()[0]->getName());
            $this->assertSame('Başlık', $email->getSubject());
            return true;
        });
        Mail::swap(\Mockery::mock(\Illuminate\Mail\MailManager::class));
        Mail::shouldReceive('build')->once()->withArgs(function ($config) {
            return $config['transport'] === 'smtp' && $config['scheme'] === 'smtps'
                && $config['host'] === 'smtp.example.test' && $config['require_tls'] === true;
        })->andReturn($transport);
        app(CampaignMailer::class)->send($delivery);
    }

    public function test_interrupted_delivery_is_flagged_without_resending_and_zero_audience_is_not_sent(): void
    {
        $user = User::factory()->create();
        $campaign = $this->campaign(['status' => 'Gönderiliyor', 'prepared_at' => now(), 'recipient_count' => 1]);
        NotificationDelivery::create(['campaign_id' => $campaign->id, 'user_id' => $user->id, 'dedupe_key' => hash('sha256', 'interrupted'),
            'event_key' => 'campaign:'.$campaign->id, 'channel' => 'email', 'status' => 'processing', 'subject' => 'Başlık',
            'content' => 'Mesaj', 'available_at' => now()->subHours(2), 'attempted_at' => now()->subHours(2)]);
        $emptyCampaign = $this->campaign(['target_groups' => ['premium'], 'scheduled_at' => now()]);
        $this->mock(CampaignMailer::class, fn ($mock) => $mock->shouldNotReceive('send'));
        app(NotificationProcessor::class)->run();
        $this->assertSame('uncertain', NotificationDelivery::sole()->status);
        $this->assertSame('Hata', $campaign->fresh()->status);
        $this->assertSame('Alıcı Yok', $emptyCampaign->fresh()->status);
        $this->assertNull($emptyCampaign->fresh()->sent_at);
    }

    public function test_deactivating_rule_cancels_pending_and_enabling_does_not_replay_history(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $rule = $this->rule(['wait_hours' => 0]);
        User::factory()->count(3)->create();
        app(NotificationProcessor::class)->run(1);
        Livewire::test(AutomationRuleForm::class, ['id' => $rule->id])->set('is_active', false)->call('save')->assertHasNoErrors();
        $this->assertSame(2, $rule->deliveries()->where('status', 'cancelled')->count());
        $this->travel(2)->hours();
        Livewire::test(AutomationRuleForm::class, ['id' => $rule->id])->set('is_active', true)->call('save')->assertHasNoErrors();
        app(NotificationProcessor::class)->run();
        $this->assertSame(1, $rule->deliveries()->where('status', 'sent')->count());
        $this->assertSame(3, NotificationDelivery::count());
    }
}

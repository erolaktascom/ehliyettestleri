<?php

namespace Tests\Feature;

use App\Models\{Integration, User};
use App\Support\GoogleLogin;
use App\Livewire\Admin\IntegrationForm;
use GuzzleHttp\{Client, HandlerStack, Middleware};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};
use Laravel\Socialite\Two\GoogleProvider;
use Livewire\Livewire;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;
    private array $history = [];

    private function enable(): Integration
    {
        $record = Integration::where('integration_key', 'google_login')->firstOrFail();
        $record->update(['is_active' => true, 'configuration' => ['client_id' => 'test.apps.googleusercontent.com', 'client_secret' => 'test-secret']]);
        return $record;
    }

    private function fakeGoogle(array $profile = [], int $tokenStatus = 200): void
    {
        $handler = HandlerStack::create(new MockHandler([
            new Response($tokenStatus, [], json_encode(['access_token' => 'fake-access-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode(array_merge(['sub' => 'google-subject-123', 'email' => 'student@example.com',
                'email_verified' => true, 'name' => 'Google Öğrenci'], $profile))),
        ]));
        $handler->push(Middleware::history($this->history));
        $client = new Client(['handler' => $handler]);
        $this->app->instance(GoogleLogin::class, new class($client) extends GoogleLogin {
            public function __construct(private Client $client) {}
            public function provider(Request $request, array $settings): GoogleProvider
            {
                return parent::provider($request, $settings)->setHttpClient($this->client);
            }
        });
    }

    private function begin(): string
    {
        $response = $this->get(route('auth.google.redirect'))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('https://accounts.google.com', 'https://'.parse_url($response->headers->get('Location'), PHP_URL_HOST));
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(GoogleLogin::callbackUrl(), $query['redirect_uri']);
        $this->assertStringNotContainsString('test-secret', $response->headers->get('Location'));
        return $query['state'];
    }

    private function completeGoogle(string $state)
    {
        return $this->get(route('auth.google.callback', ['code' => 'test-code', 'state' => $state]));
    }

    public function test_disabled_or_incomplete_integration_has_no_button_and_no_network(): void
    {
        $this->fakeGoogle();
        $this->get('/login')->assertOk()->assertDontSee('Google ile Devam Et');
        $this->get('/register')->assertOk()->assertDontSee('Google ile Devam Et');
        $this->get(route('auth.google.redirect'))->assertRedirect(route('login'));
        $record = $this->enable();
        $record->update(['configuration' => ['client_id' => 'only-id']]);
        $this->get(route('auth.google.redirect'))->assertRedirect(route('login'));
        $this->assertCount(0, $this->history);
    }

    public function test_real_provider_state_pkce_new_student_and_returning_subject_login(): void
    {
        $this->enable(); $this->fakeGoogle();
        $this->get('/login')->assertSee('Google ile Devam Et');
        $this->get('/register')->assertSee('Google ile Devam Et');
        $state = $this->begin();
        $this->completeGoogle($state)->assertRedirect(route('dashboard'));
        $user = User::where('google_id', 'google-subject-123')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);
        $this->assertSame('Öğrenci', $user->role_name);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertCount(2, $this->history);
        parse_str((string) $this->history[0]['request']->getBody(), $body);
        $this->assertSame('test-secret', $body['client_secret']);
        $this->assertNotEmpty($body['code_verifier']);
        $this->assertSame('Bearer fake-access-token', $this->history[1]['request']->getHeaderLine('Authorization'));
        $this->post('/logout');
        $this->fakeGoogle(['email' => 'changed@example.com']);
        $this->completeGoogle($this->begin())->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('student@example.com', $user->fresh()->email);
        $this->assertSame(1, User::where('google_id', 'google-subject-123')->count());
    }

    public function test_invalid_state_expiry_replay_and_config_changes_fail_before_network(): void
    {
        $record = $this->enable(); $this->fakeGoogle();
        $state = $this->begin();
        $this->completeGoogle('wrong-state')->assertRedirect(route('login'));
        $this->completeGoogle($state)->assertRedirect(route('login'));
        $this->assertGuest(); $this->assertCount(0, $this->history);
        $state = $this->begin(); $this->travel(11)->minutes();
        $this->completeGoogle($state)->assertRedirect(route('login')); $this->travelBack();
        $state = $this->begin(); $record->update(['is_active' => false]);
        $this->completeGoogle($state)->assertRedirect(route('login'));
        $record = $this->enable(); $state = $this->begin();
        $record->update(['configuration' => ['client_id' => 'changed', 'client_secret' => 'different']]);
        $this->completeGoogle($state)->assertRedirect(route('login'));
        $this->assertCount(0, $this->history);
    }

    public function test_denied_consent_unverified_email_and_provider_errors_fail_safely(): void
    {
        $this->enable(); $this->fakeGoogle(); $state = $this->begin();
        $this->get(route('auth.google.callback', ['state' => $state, 'error' => 'access_denied']))->assertRedirect(route('login'));
        $this->assertCount(0, $this->history);
        $this->fakeGoogle(['email_verified' => false]);
        $this->completeGoogle($this->begin())->assertRedirect(route('login')); $this->assertGuest();
        $this->assertSame(0, User::count());
        $this->fakeGoogle([], 400);
        $this->completeGoogle($this->begin())->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_existing_email_requires_password_before_linking_and_staff_are_blocked(): void
    {
        $this->enable(); $this->fakeGoogle();
        $existing = User::factory()->create(['email' => 'Student@example.com', 'password' => Hash::make('correct-password')]);
        $this->completeGoogle($this->begin())->assertRedirect(route('auth.google.link'));
        $this->assertGuest(); $this->assertNull($existing->fresh()->google_id);
        $this->get(route('auth.google.link'))->assertOk()->assertSee('mevcut EhliyetGo şifrenizi');
        $this->from(route('auth.google.link'))->post(route('auth.google.link.submit'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertNull($existing->fresh()->google_id);
        $this->post(route('auth.google.link.submit'), ['password' => 'correct-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-subject-123', $existing->fresh()->google_id);
        $this->assertTrue(Hash::check('correct-password', $existing->fresh()->password));
        $this->post('/logout');
        $existing->update(['is_admin' => true, 'role_name' => 'Yönetici']);
        $this->fakeGoogle();
        $this->completeGoogle($this->begin())->assertRedirect(route('login')); $this->assertGuest();
    }

    public function test_link_requires_pending_session_and_respects_disable_and_expiry(): void
    {
        $record = $this->enable(); $this->fakeGoogle();
        $existing = User::factory()->create(['email' => 'student@example.com']);
        $this->post(route('auth.google.link.submit'), ['password' => 'password'])->assertRedirect(route('login'));
        $this->completeGoogle($this->begin()); $this->travel(11)->minutes();
        $this->post(route('auth.google.link.submit'), ['password' => 'password'])->assertRedirect(route('login'));
        $this->travelBack(); $this->fakeGoogle(); $this->completeGoogle($this->begin());
        $record->update(['is_active' => false]);
        $this->post(route('auth.google.link.submit'), ['password' => 'password'])->assertRedirect(route('login'));
        $this->assertNull($existing->fresh()->google_id); $this->assertGuest();
    }

    public function test_google_user_can_set_first_password_but_existing_users_must_supply_current_password(): void
    {
        $this->enable(); $this->fakeGoogle(); $this->completeGoogle($this->begin());
        $user = Auth::user();
        $this->get(route('student.settings.profile'))->assertOk()->assertSee('Şifre Oluştur')->assertDontSee('name="current_password"', false);
        $this->put(route('student.settings.password.update'), ['password' => 'new-secret', 'password_confirmation' => 'new-secret'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-secret', $user->fresh()->password));
        $this->put(route('student.settings.password.update'), ['password' => 'another-secret', 'password_confirmation' => 'another-secret'])
            ->assertSessionHasErrors('current_password');
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'new-secret'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_enable_with_credentials_and_seo_rules_cannot_capture_oauth(): void
    {
        $admin = User::factory()->create(['is_admin' => true]); $this->actingAs($admin);
        $record = Integration::where('integration_key', 'google_login')->firstOrFail();
        Livewire::test(IntegrationForm::class, ['id' => $record->id])->set('fields.client_id', 'test.apps.googleusercontent.com')
            ->set('fields.client_secret', 'test-secret')->set('is_active', true)->call('save')->assertHasNoErrors();
        $this->assertTrue($record->fresh()->is_active);
        $this->post('/logout'); $this->fakeGoogle();
        \App\Models\SeoRedirect::create(['old_url' => '/auth/google/redirect', 'new_url' => 'https://example.org', 'type' => '301', 'status' => 'Aktif']);
        $this->begin();
        $this->assertCount(0, $this->history);
    }

    public function test_account_link_password_attempts_are_rate_limited(): void
    {
        $this->enable(); $this->fakeGoogle();
        $user = User::factory()->create(['email' => 'student@example.com']);
        $this->completeGoogle($this->begin());
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('auth.google.link.submit'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        }
        $this->post(route('auth.google.link.submit'), ['password' => 'password'])->assertStatus(429);
        $this->assertNull($user->fresh()->google_id);
        $this->assertGuest();
    }

    public function test_existing_staff_or_different_google_identity_cannot_be_linked_by_email(): void
    {
        $this->enable(); $this->fakeGoogle();
        $user = User::factory()->create(['email' => 'student@example.com', 'is_admin' => true]);
        $this->completeGoogle($this->begin())->assertRedirect(route('login'));
        $this->assertGuest(); $this->assertNull($user->fresh()->google_id);
        $user->is_admin = false; $user->google_id = 'other-google-identity'; $user->save();
        $this->fakeGoogle();
        $this->completeGoogle($this->begin())->assertRedirect(route('login'));
        $this->assertGuest(); $this->assertSame('other-google-identity', $user->fresh()->google_id);
    }
}

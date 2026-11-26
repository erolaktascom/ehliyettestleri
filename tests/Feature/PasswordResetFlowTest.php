<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\CampaignMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Password};
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_links_and_reset_pages_are_private_and_not_seo_redirectable(): void
    {
        \App\Models\Advertisement::create(['title'=>'Test ad','location'=>'Header','device'=>'desktop','audience'=>['everyone'],'is_active'=>true,'code'=>'<script>window.resetPageAd = true;</script>']);
        foreach (['/login', '/admin/login'] as $path) {
            $this->get($path)->assertOk()->assertSee(route('password.request'), false);
        }
        foreach (['/sifremi-unuttum', '/sifre-yenile/example?email=member%40example.com'] as $path) {
            $this->get($path)->assertOk()->assertSee('noindex, nofollow', false)
                ->assertDontSee('rel="canonical"', false)->assertDontSee('window.resetPageAd', false);
            $this->assertTrue(\App\Support\SeoRedirectRules::protectedPath(parse_url($path, PHP_URL_PATH)));
        }
    }

    public function test_inactive_smtp_does_not_claim_success_or_create_a_token(): void
    {
        $user = User::factory()->create();
        $this->from('/sifremi-unuttum')->post(route('password.email'), ['email'=>$user->email])
            ->assertRedirect('/sifremi-unuttum')->assertSessionHasErrors('email')->assertSessionMissing('status');
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_mail_contains_a_single_use_link_for_the_configured_site_and_unknown_accounts_get_the_same_message(): void
    {
        config(['app.url'=>'https://trusted.example']);
        $user = User::factory()->create();
        $body = '';
        $this->mock(CampaignMailer::class, function ($mock) use ($user, &$body) {
            $mock->shouldReceive('settings')->andReturn(['configured'=>true]);
            $mock->shouldReceive('sendMessage')->once()->withArgs(function ($email, $subject, $content) use ($user, &$body) {
                $body = $content;
                return $email === $user->email && str_contains($subject, 'Şifre sıfırlama');
            });
        });
        $this->withHeader('Host', 'untrusted.example')->post('/sifremi-unuttum', ['email'=>$user->email])
            ->assertSessionHas('status')->assertSessionHasNoErrors();
        $firstMessage = session('status');
        $this->assertStringContainsString('https://trusted.example/sifre-yenile/', $body);
        $this->assertStringNotContainsString('untrusted.example', $body);
        preg_match('~/sifre-yenile/([a-zA-Z0-9]+)~', $body, $matches);
        $this->assertTrue(Password::broker()->tokenExists($user, $matches[1]));
        $this->assertNotSame($matches[1], DB::table('password_reset_tokens')->value('token'));
        $this->post('/sifremi-unuttum', ['email'=>$user->email])->assertSessionHas('status', $firstMessage);
        $this->post('/sifremi-unuttum', ['email'=>'missing@example.com'])->assertSessionHas('status', $firstMessage);
    }

    public function test_transport_failure_removes_token_and_shows_an_error(): void
    {
        $user = User::factory()->create();
        $this->mock(CampaignMailer::class, function ($mock) {
            $mock->shouldReceive('settings')->andReturn(['configured'=>true]);
            $mock->shouldReceive('sendMessage')->once()->andThrow(new \RuntimeException('private smtp details'));
        });
        $this->post('/sifremi-unuttum', ['email'=>$user->email])->assertSessionHasErrors('email')->assertSessionMissing('status');
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_successful_reset_changes_password_revokes_sessions_and_cannot_be_reused(): void
    {
        $user = User::factory()->create(['password'=>'OldPassword123', 'remember_token'=>'old-remember']);
        $other = User::factory()->create();
        foreach ([$user, $other] as $person) DB::table('sessions')->insert([
            'id'=>'session-'.$person->id, 'user_id'=>$person->id, 'payload'=>'', 'last_activity'=>time(),
        ]);
        $token = Password::broker()->createToken($user);
        config(['session.driver'=>'database']);
        $this->post('/sifre-yenile', $this->credentials($user, $token))->assertRedirect(route('login'))->assertSessionHas('success');
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->assertNotSame('old-remember', $user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id'=>'session-'.$user->id]);
        $this->assertDatabaseHas('sessions', ['id'=>'session-'.$other->id]);
        $this->assertGuest();
        $this->post('/sifre-yenile', $this->credentials($user, $token))->assertSessionHasErrors('email');
    }

    public function test_expired_token_wrong_email_and_invalid_password_are_rejected(): void
    {
        $user = User::factory()->create(['password'=>'OldPassword123']);
        $other = User::factory()->create();
        $token = Password::broker()->createToken($user);
        $this->post('/sifre-yenile', $this->credentials($other, $token))->assertSessionHasErrors('email');
        $data = $this->credentials($user, $token);
        $data['password_confirmation'] = 'Mismatch';
        $this->post('/sifre-yenile', $data)->assertSessionHasErrors('password');
        $this->travel(61)->minutes();
        $this->post('/sifre-yenile', $this->credentials($user, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
    }

    public function test_admin_is_sent_back_to_admin_login_and_requests_are_rate_limited(): void
    {
        $user = User::factory()->create(['is_admin'=>true]);
        $token = Password::broker()->createToken($user);
        $this->post('/sifre-yenile', $this->credentials($user, $token))->assertRedirect(route('admin.login'));
        $this->get('/admin/login')->assertSee('Şifreniz yenilendi.');
        $this->mock(CampaignMailer::class, fn ($mock) => $mock->shouldReceive('settings')->andReturn(null));
        for ($i=0; $i<5; $i++) $this->post('/sifremi-unuttum', ['email'=>$user->email])->assertStatus(302);
        $this->post('/sifremi-unuttum', ['email'=>$user->email])->assertStatus(429);
    }

    private function credentials(User $user, string $token): array
    {
        return ['email'=>$user->email, 'token'=>$token, 'password'=>'NewPassword123', 'password_confirmation'=>'NewPassword123'];
    }
}

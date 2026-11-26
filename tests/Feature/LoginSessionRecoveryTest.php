<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginSessionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Exercise real CSRF failures; Laravel normally skips this check in tests.
        $this->app->bind(ValidateCsrfToken::class, EnforcedLoginCsrfToken::class);
    }

    public function test_expired_guest_login_returns_to_the_form_without_retaining_the_password(): void
    {
        foreach (['/login', '/admin/login'] as $path) {
            $this->post($path, [
                '_token' => 'expired', 'email' => 'admin@example.test', 'password' => 'example-test-password',
            ])->assertRedirect($path)->assertSessionHasErrors('email')
                ->assertSessionHasInput('email', 'admin@example.test')
                ->assertSessionMissing('_old_input.password');
            $this->assertGuest();
            $this->get($path)->assertOk()->assertSee('Giriş formunun süresi doldu');
        }
    }

    public function test_an_already_logged_in_admin_with_a_stale_form_reaches_the_panel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post('/admin/login', ['_token' => 'expired'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_student_with_a_stale_admin_form_is_not_granted_admin_access(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->post('/admin/login', ['_token' => 'expired'])
            ->assertRedirect(route('admin.login'))->assertSessionHasErrors('email');
        $this->get('/admin')->assertForbidden();
        $this->post('/login', ['_token' => 'expired'])->assertRedirect(route('dashboard'));
    }

    public function test_other_post_routes_still_reject_invalid_csrf_tokens(): void
    {
        $this->post('/register', [
            '_token' => 'expired', 'name' => 'Test', 'email' => 'test@example.com',
            'password' => 'example-test-password', 'password_confirmation' => 'example-test-password',
        ])->assertStatus(419);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_can_grade_with_the_refreshed_session_after_a_csrf_mismatch(): void
    {
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme', 'is_published' => true, 'order' => 1]);
        $question = $exam->questions()->create([
            'question_text' => 'Soru', 'status' => 'Yayında', 'correct_option' => 'A',
            'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']],
        ]);
        $url = route('guest.exam-result', $exam);
        $answers = ['question_ids' => [$question->id], 'answers' => [$question->id => 'A']];
        $this->postJson($url, $answers, ['X-CSRF-TOKEN' => 'expired'])->assertStatus(419);

        $session = $this->getJson(route('guest.session-token'))->assertOk();
        $this->assertTrue($session->headers->hasCacheControlDirective('no-store'));
        $cookieName = config('session.cookie');
        $this->withCookie($cookieName, $session->getCookie($cookieName)->getValue())->withCredentials()
            ->postJson($url, $answers, ['X-CSRF-TOKEN' => $session->json('token')])
            ->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, 'BAŞARILI'));
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertDatabaseCount('exam_attempt_answers', 0);
    }
}

class EnforcedLoginCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}

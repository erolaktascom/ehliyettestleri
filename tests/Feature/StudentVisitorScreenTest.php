<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentVisitorScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }
    public function test_visitor_sees_landing_page_and_guest_header(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Ehliyet sınavına');
        $response->assertSee('hazır mısın?');
        $response->assertSee('Üye Girişi');
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//header//a[@href="'.route('register').'"]')->length);
        $this->assertSame(2, $xpath->query('//header//a[@href="'.route('login').'" and normalize-space(.)="Üye Girişi"]')->length);
        $response->assertDontSee('Öğrenci Portalı');
    }

    public function test_authenticated_student_redirects_to_dashboard(): void
    {
        $user = User::where('email', 'ogrenci@example.test')->first();
        if (!$user) {
            $user = User::factory()->create(['email' => 'ogrenci@example.test', 'name' => 'Örnek Öğrenci']);
        }

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect(route('dashboard'));

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Öğrenci Portalı');
        $dashboardResponse->assertSee('Genel Bakış');
        $dashboardResponse->assertSee('Hazırlık oranı');
        $dashboardResponse->assertSee('Deneme sınavları');
        $dashboardResponse->assertSee('Konu kategorileri');
        $dashboardResponse->assertSee('Öğrenme alanları');
        $dashboardResponse->assertSee('Kaydedilen Sorular');
    }

    public function test_student_can_access_subpages(): void
    {
        $user = User::where('email', 'ogrenci@example.test')->first();
        if (!$user) {
            $user = User::factory()->create(['email' => 'ogrenci@example.test', 'name' => 'Örnek Öğrenci']);
        }

        // Profile settings
        $res = $this->actingAs($user)->get('/settings/profile');
        $res->assertStatus(200);
        $res->assertSee('Hesap Ayarları');

        // Detailed performance requires an active Premium subscription.
        $this->actingAs($user)->get('/detayli-performans')->assertRedirect(route('student.premium'));
        $user->update(['premium_until' => now()->addMonth()]);
        $res = $this->actingAs($user)->get('/detayli-performans');
        $res->assertStatus(200);
        $res->assertSee('Detaylı Performans');

        // Saved questions
        $res = $this->actingAs($user)->get('/kaydedilen-sorular');
        $res->assertStatus(200);
        $res->assertSee('Kaydettiğim Sorular');

        // Premium
        $res = $this->actingAs($user)->get('/premium');
        $res->assertStatus(200);
        $res->assertSee('Premium ile daha verimli hazırlan');
    }

    public function test_profile_save_shows_one_confirmation_and_one_account_menu(): void
    {
        $user = User::factory()->create();
        $profile = route('student.settings.profile');
        $this->actingAs($user)->from($profile)->put(route('student.settings.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
        ])->assertSessionHasNoErrors()->assertRedirect($profile);

        $html = $this->get($profile)->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'Profil bilgileriniz başarıyla güncellendi.'));
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new \DOMXPath($dom);
        $account = '//header//details[summary[@aria-label="Kullanıcı hesabım"]]';
        foreach (['a[normalize-space(.)="Genel Bakış"]', 'a[@href="'.$profile.'"]', 'form[@action="'.route('logout').'"]'] as $action) {
            $this->assertSame(1, $xpath->query($account.'//'.$action)->length);
            $this->assertSame(0, $xpath->query('//*[@id="mobile-site-menu"]//'.$action)->length);
        }
        $this->assertStringNotContainsString($user->email, $xpath->query('//*[@id="mobile-site-menu"]')->item(0)->textContent);
    }
}

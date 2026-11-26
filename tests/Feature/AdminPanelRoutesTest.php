<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Advertisement;
use App\Models\AutomationRule;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Exam;
use App\Models\Integration;
use App\Models\Lecture;
use App\Models\LectureCategory;
use App\Models\NavigationItem;
use App\Models\NotificationCampaign;
use App\Models\Page;
use App\Models\PremiumPlan;
use App\Models\Question;
use App\Models\QuestionImport;
use App\Models\TopicCategory;
use App\Models\SeoRedirect;
use App\Models\TopicTest;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('is_admin', true)->first() ?? User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);

        $this->student = User::where('is_admin', false)->first() ?? User::factory()->create([
            'name' => 'Student Test',
            'email' => 'student@test.com',
            'is_admin' => false,
        ]);
    }

    public function test_admin_login_page_renders_with_brand(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('Ehliyet');
        $response->assertSee('Yönetim Paneli Girişi');
        $response->assertSee('Oturum Aç');
    }

    public function test_guest_is_redirected_to_admin_login_when_accessing_admin_routes(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('admin.login'));

        $response = $this->get('/admin/questions');
        $response->assertRedirect(route('admin.login'));
    }

    public function test_student_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->student)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_user_can_authenticate_via_admin_login(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'example-test-password',
        ]);
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_all_blade_and_livewire_admin_pages_render_successfully(): void
    {
        $this->actingAs($this->admin);

        $routes = [
            // List Pages
            '/admin' => 'Dashboard',
            '/admin/reports' => 'Raporlar',
            '/admin/question-topics' => 'Soru Kategorileri',
            '/admin/questions' => 'Sorular',
            '/admin/question-imports' => 'Toplu Soru Aktarımı',
            '/admin/exams' => 'Sınavlar',
            '/admin/topic-tests' => 'Konu Testleri',
            '/admin/content-categories' => 'Konu Kategorileri',
            '/admin/topics' => 'Konu Anlatımları',
            '/admin/pages' => 'Sayfalar',
            '/admin/traffic-signs' => 'Trafik İşaretleri',
            '/admin/traffic-sign-categories' => 'İşaret Kategorileri',
            '/admin/blog-categories' => 'Blog Kategorileri',
            '/admin/blog-posts' => 'Blog Yazıları',
            '/admin/admin-roles' => 'Rol ve İzinler',
            '/admin/staff-users' => 'Yönetim Ekibi',
            '/admin/users' => 'Kullanıcılar',
            '/admin/premium-plans' => 'Premium Paketleri',
            '/admin/notification-campaigns' => 'Bildirimler',
            '/admin/automation-rules' => 'Otomasyon Kuralları',
            '/admin/seo-settings' => 'SEO Ayarları',
            '/admin/seo-redirects' => 'Yönlendirmeler',
            '/admin/seo-not-found-logs' => '404 Kayıtları',
            '/admin/integrations' => 'Entegrasyonlar',
            '/admin/advertisements' => 'Reklam Alanları',
            '/admin/audit-logs' => 'İşlem Geçmişi',
            '/admin/contact-messages' => 'İletişim Mesajları',
            '/admin/navigation-items' => 'Menü Yönetimi',
            '/admin/settings' => 'Site Ayarları',

            // Create Pages (Dedicated Full-Page Forms)
            '/admin/questions/create' => 'Soru Ekle',
            '/admin/question-topics/create' => 'Soru Kategorisi Ekle',
            '/admin/question-imports/create' => 'Soru Aktarım Kaydı',
            '/admin/exams/create' => 'Sınav Ekle',
            '/admin/topic-tests/create' => 'Konu Testi Ekle',
            '/admin/content-categories/create' => 'Konu Kategorisi Ekle',
            '/admin/topics/create' => 'Ders Ekle',
            '/admin/pages/create' => 'Sayfa Ekle',
            '/admin/traffic-signs/create' => 'Levha Ekle',
            '/admin/traffic-sign-categories/create' => 'İşaret Kategorisi Ekle',
            '/admin/blog-categories/create' => 'Blog Kategorisi Ekle',
            '/admin/blog-posts/create' => 'Blog Yazısı Ekle',
            '/admin/staff-users/create' => 'Yönetici Ekle',
            '/admin/users/create' => 'Kullanıcı Ekle',
            '/admin/premium-plans/create' => 'Paket Ekle',
            '/admin/notification-campaigns/create' => 'Bildirim Ekle',
            '/admin/automation-rules/create' => 'Kural Ekle',
            '/admin/seo-redirects/create' => 'Yönlendirme Ekle',
            '/admin/advertisements/create' => 'Reklam Alanı Ekle',
            '/admin/navigation-items/create' => 'Menü Öğesi Ekle',
        ];

        foreach ($routes as $url => $label) {
            $response = $this->get($url);
            $this->assertEquals(200, $response->getStatusCode(), "Route {$url} ({$label}) failed with status {$response->getStatusCode()}");
            $response->assertSee('Ehliyet');
        }
    }

    public function test_all_dedicated_edit_form_pages_render_successfully(): void
    {
        $this->actingAs($this->admin);

        // Ensure records exist for testing edit forms
        $qTopic = TopicCategory::first() ?? TopicCategory::create(['name' => 'Trafik ve Cevre', 'slug' => 'trafik-ve-cevre', 'order' => 1, 'is_active' => true]);
        $question = Question::first() ?? Question::create([
            'question_text' => 'Test soru',
            'explanation' => 'Test aciklama',
            'correct_answer' => 'A',
            'options' => ['A' => 'Cevap A', 'B' => 'Cevap B'],
            'is_active' => true,
        ]);
        $exam = Exam::first() ?? Exam::create([
            'title' => 'Test Sinavi',
            'slug' => 'test-sinavi',
            'duration' => 45,
            'passing_score' => 70,
            'is_active' => true,
        ]);
        $topicTest = TopicTest::first() ?? TopicTest::create([
            'title' => 'Test Konu',
            'slug' => 'test-konu',
            'duration' => 30,
            'is_active' => true,
        ]);
        $cCat = LectureCategory::first() ?? LectureCategory::create(['name' => 'Kategori 1', 'slug' => 'kategori-1', 'order' => 1]);
        $topic = Lecture::first() ?? Lecture::create([
            'category_id' => $cCat->id,
            'title' => 'Ders 1',
            'slug' => 'ders-1',
            'content' => 'Icerik',
            'order' => 1,
            'is_active' => true,
        ]);
        $signCat = TrafficSignCategory::first() ?? TrafficSignCategory::create(['name' => 'Tehlike', 'slug' => 'tehlike', 'order' => 1]);
        $sign = TrafficSign::first() ?? TrafficSign::create(['category_id' => $signCat->id, 'name' => 'Dur', 'slug' => 'dur', 'description' => 'Dur levhasi', 'order' => 1]);
        $blogCat = BlogCategory::first() ?? BlogCategory::create(['name' => 'Rehber', 'slug' => 'rehber', 'order' => 1]);
        $post = BlogPost::first() ?? BlogPost::create(['category_id' => $blogCat->id, 'title' => 'Yazi 1', 'slug' => 'yazi-1', 'content' => 'Yazi icerigi', 'is_published' => true]);
        $page = Page::first() ?? Page::create(['title' => 'Hakkimizda', 'slug' => 'hakkimizda', 'content' => 'Icerik', 'is_active' => true]);
        $role = AdminRole::where('key', 'editor')->firstOrFail();
        $plan = PremiumPlan::first() ?? PremiumPlan::create(['name' => 'Aylik', 'short_code' => 'aylik', 'duration_days' => 30, 'price_cents' => 10000, 'order' => 1, 'is_published' => true]);
        $nav = NavigationItem::first() ?? NavigationItem::create(['name' => 'Anasayfa', 'url' => '/', 'audience' => 'everyone', 'menu' => 'desktop', 'order' => 1]);
        $rule = AutomationRule::first() ?? AutomationRule::create(['name' => 'Kural 1', 'channels' => 'email', 'wait_hours' => 24, 'is_active' => true]);
        $campaign = NotificationCampaign::first() ?? NotificationCampaign::create(['title' => 'Kampanya 1', 'type' => 'email', 'target' => 'all', 'is_email' => true, 'status' => 'draft']);
        $ad = Advertisement::first() ?? Advertisement::create(['title' => 'Banner 1', 'location' => 'header', 'is_active' => true]);
        $integration = Integration::where('integration_key', 'google_login')->firstOrFail();
        $redirect = SeoRedirect::first() ?? SeoRedirect::create(['old_url' => '/eski-url', 'new_url' => '/yeni-url', 'type' => '301', 'status' => 'active']);
        $import = QuestionImport::first() ?? QuestionImport::create(['file_name' => 'sorular.xlsx', 'user_id' => $this->admin->id, 'status' => 'completed', 'total' => 10, 'ready' => 10, 'failed' => 0, 'duplicate' => 0, 'imported' => 10]);

        $editRoutes = [
            "/admin/questions/{$question->id}/edit",
            "/admin/exams/{$exam->id}/edit",
            "/admin/topic-tests/{$topicTest->id}/edit",
            "/admin/topics/{$topic->id}/edit",
            "/admin/pages/{$page->id}/edit",
            "/admin/traffic-signs/{$sign->id}/edit",
            "/admin/blog-posts/{$post->id}/edit",
            "/admin/question-topics/{$qTopic->id}/edit",
            "/admin/content-categories/{$cCat->id}/edit",
            "/admin/traffic-sign-categories/{$signCat->id}/edit",
            "/admin/blog-categories/{$blogCat->id}/edit",
            "/admin/admin-roles/{$role->id}/edit",
            "/admin/staff-users/{$this->admin->id}/edit",
            "/admin/users/{$this->admin->id}/edit",
            "/admin/premium-plans/{$plan->id}/edit",
            "/admin/notification-campaigns/{$campaign->id}/edit",
            "/admin/automation-rules/{$rule->id}/edit",
            "/admin/seo-redirects/{$redirect->id}/edit",
            "/admin/integrations/{$integration->id}/edit",
            "/admin/advertisements/{$ad->id}/edit",
            "/admin/navigation-items/{$nav->id}/edit",
            "/admin/question-imports/{$import->id}/edit",
        ];

        foreach ($editRoutes as $url) {
            $response = $this->get($url);
            $this->assertEquals(200, $response->getStatusCode(), "Edit route {$url} failed with status {$response->getStatusCode()}");
            $response->assertSee('Ehliyet');
        }
    }

    public function test_admin_can_logout(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/logout');
        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}

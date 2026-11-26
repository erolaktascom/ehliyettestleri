<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LectureController;
use App\Http\Controllers\TopicTestController;
use App\Http\Controllers\TrafficSignController;
use Illuminate\Support\Facades\Route;

// XML sitemaps and robots are generated from the saved panel settings.
Route::get('/sitemap_index.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap.alias');
Route::get('/{section}-sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'section'])->where('section', implode('|', array_keys(\App\Support\SiteSitemap::SECTIONS)))->name('sitemap.section');
Route::get('/{section}-sitemap-{page}.xml', [\App\Http\Controllers\SitemapController::class, 'section'])->where('section', implode('|', array_keys(\App\Support\SiteSitemap::SECTIONS)))->where('page', '[2-9]|[1-9][0-9]{1,7}')->name('sitemap.section.page');
Route::get('/robots.txt', [\App\Http\Controllers\SitemapController::class, 'robots'])->name('site.robots');

// Ana Sayfa & Kurumsal & Statik Sayfalar
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/arama', \App\Http\Controllers\SearchController::class)->name('search');
Route::get('/hakkimizda', [\App\Http\Controllers\PageController::class, 'show'])->defaults('slug', 'hakkimizda')->name('about');


// Deneme Sınavları
Route::get('/deneme', [ExamController::class, 'index'])->name('exams.index');
Route::get('/deneme/{exam:slug}', [ExamController::class, 'play'])->name('exams.play')->block(20, 20);
Route::post('/deneme/{exam:slug}', [ExamController::class, 'start'])->name('exams.start')->block(20, 20);
Route::post('/deneme/{exam:slug}/baslat', [ExamController::class, 'start'])->block(20, 20);
Route::get('/deneme/{slug}/baslat', fn (string $slug) => to_route('exams.play', ['exam' => $slug]));
Route::get('/sinav/oturum/{attempt}', [ExamController::class, 'take'])->name('exams.take');
Route::post('/sinav/oturum/{attempt}/cevap', [ExamController::class, 'saveAnswer'])->name('exams.save-answer')->block(20, 20);
Route::post('/sinav/oturum/{attempt}/bitir', [ExamController::class, 'finish'])->name('exams.finish')->block(20, 20);
Route::post('/sinav/oturum/{attempt}/konum', [ExamController::class, 'position'])->name('exams.position')->block(20, 20);
Route::post('/sinav/oturum/{attempt}/ayril', [ExamController::class, 'leave'])->name('exams.leave')->block(20, 20);
Route::get('/sinav/oturum/{attempt}/sonuc', [ExamController::class, 'result'])->name('exams.result')->block(20, 20);

// Konu Testleri & Animasyonlu Sorular
Route::get('/konu-testleri', [TopicTestController::class, 'categories'])->name('topic-tests.categories');
Route::get('/konu-testleri/{category:slug}', [TopicTestController::class, 'category'])->name('topic-tests.category');
Route::post('/konu-testleri/{test}/baslat', [TopicTestController::class, 'start'])->whereNumber('test')->name('topic-tests.start')->block(20, 20);
Route::get('/konu-testleri/oturum/{attempt}', [TopicTestController::class, 'take'])->whereNumber('attempt')->name('topic-tests.take');
Route::get('/konu-testleri/{category:slug}/{test:slug}', [TopicTestController::class, 'play'])->withoutScopedBindings()->name('topic-tests.play')->block(20, 20);
Route::get('/animasyonlu-sorular/{test:slug}', [TopicTestController::class, 'animatedPlay'])->name('animated-questions.play')->block(20, 20);
Route::get('/animasyonlu-sorular', [TopicTestController::class, 'animated'])->name('animated-questions.index');

Route::post('/deneme/{exam:slug}/degerlendir', [\App\Http\Controllers\GuestPracticeController::class, 'examResult'])->name('guest.exam-result');
Route::get('/sinav/oturum/ziyaretci/yenile', [\App\Http\Controllers\GuestPracticeController::class, 'sessionToken'])->name('guest.session-token');
Route::post('/konu-testleri/{test}/degerlendir', [\App\Http\Controllers\GuestPracticeController::class, 'topicResult'])->whereNumber('test')->name('guest.topic-result');

// Trafik İşaretleri
Route::get('/trafik-isaretleri', [TrafficSignController::class, 'index'])->name('traffic-signs.index');
Route::get('/ogren/trafik-isaretleri/{category:slug}', [TrafficSignController::class, 'learn'])->name('traffic-signs.learn');
Route::post('/trafik-isaretleri/{sign}/incele', [TrafficSignController::class, 'saveReview'])->name('traffic-signs.save-review')->middleware('auth');
Route::post('/ogren/trafik-isaretleri/{category:slug}/konum', [TrafficSignController::class, 'position'])->name('traffic-signs.position')->middleware('auth');

// Konu Anlatımları
Route::get('/konu', [LectureController::class, 'index'])->name('lectures.index');
// The former /ders-notlari URL is managed through SEO redirects.
Route::get('/konu/{category:slug}', [LectureController::class, 'category'])->name('lectures.category');
Route::get('/konu/{category:slug}/{lecture:slug}', [LectureController::class, 'show'])->name('lectures.show');
Route::post('/konu/ders/{lecture}/tamamla', [LectureController::class, 'toggleComplete'])->name('lectures.toggle-complete')->middleware('auth');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
$blogPage = '[2-9]|[1-9][0-9]{1,8}';
Route::get('/blog/sayfa/{page}', [BlogController::class, 'index'])->where('page', $blogPage)->name('blog.index.page');
Route::get('/kategori/{category:slug}/{page?}', [BlogController::class, 'category'])->where('page', $blogPage)->name('blog.category');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/yazar/{slug}/{page?}', [BlogController::class, 'author'])->where('page', $blogPage)->name('blog.author');

// İletişim
Route::get('/iletisim', [ContactController::class, 'show'])->name('contact.show');
Route::post('/iletisim', [ContactController::class, 'submit'])->name('contact.submit');

Route::get('/uye/{slug}', \App\Http\Controllers\MemberProfileController::class)->name('members.show');

// Şifre sıfırlama: aynı SMTP bağlantısı, tek kullanımlık ve süreli belirteç.
Route::middleware('guest')->group(function () {
    Route::get('/sifremi-unuttum', [\App\Http\Controllers\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/sifremi-unuttum', [\App\Http\Controllers\PasswordResetController::class, 'email'])->middleware('throttle:5,1,password-email')->name('password.email');
    Route::get('/sifre-yenile/{token}', [\App\Http\Controllers\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/sifre-yenile', [\App\Http\Controllers\PasswordResetController::class, 'update'])->middleware('throttle:10,1,password-reset')->name('password.update');
});

// Üyelik & Kimlik Doğrulama
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['guest', 'throttle:20,1,google-flow'])->group(function () {
    Route::get('/auth/google/redirect', [\App\Http\Controllers\GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    Route::get('/auth/google/link', [\App\Http\Controllers\GoogleAuthController::class, 'showLink'])->name('auth.google.link');
    Route::post('/auth/google/link', [\App\Http\Controllers\GoogleAuthController::class, 'link'])->middleware('throttle:5,1,google-link')->name('auth.google.link.submit');
});

// Öğrenci Portalı & Dashboard
Route::middleware('auth')->group(function () {
    Route::get('/bildirimler', [\App\Http\Controllers\MemberNotificationController::class, 'index'])->name('student.notifications');
    Route::post('/bildirimler/{id}/okundu', [\App\Http\Controllers\MemberNotificationController::class, 'read'])->whereNumber('id')->name('student.notifications.read');
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [\App\Http\Controllers\DashboardController::class, 'index'])->name('profile');
    Route::get('/settings/profile', [\App\Http\Controllers\DashboardController::class, 'profileSettings'])->name('student.settings.profile');
    Route::put('/settings/profile', [\App\Http\Controllers\DashboardController::class, 'updateProfile'])->name('student.settings.profile.update');
    Route::put('/settings/password', [\App\Http\Controllers\DashboardController::class, 'updatePassword'])->name('student.settings.password.update');
    Route::get('/detayli-performans', [\App\Http\Controllers\DashboardController::class, 'detailedPerformance'])->name('student.detailed-performance');
    Route::get('/kaydedilen-sorular', [\App\Http\Controllers\DashboardController::class, 'savedQuestions'])->name('student.saved-questions');
    Route::post('/soru/{question}/kaydet', [\App\Http\Controllers\DashboardController::class, 'toggleSaveQuestion'])->name('questions.toggle-save');
    Route::get('/premium', [\App\Http\Controllers\DashboardController::class, 'premium'])->name('student.premium');
    Route::post('/yanlislarim/baslat', [\App\Http\Controllers\DashboardController::class, 'startWrongQuestionsExam'])->name('student.wrong-questions.start');
});

// Admin Paneli (Laravel Blade + Livewire + Tailwind CSS)
Route::prefix('admin')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [\App\Http\Controllers\Admin\AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware(['admin'])->group(function () {
        // Genel
        Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');
        Route::get('/access', \App\Livewire\Admin\AccessNotice::class)->name('admin.access');
        Route::get('/reports', \App\Livewire\Admin\Reports::class)->name('admin.reports');


        // Sınav Yönetimi
        Route::get('/question-topics', \App\Livewire\Admin\QuestionTopics::class)->name('admin.question-topics');
        Route::get('/question-topics/create', \App\Livewire\Admin\QuestionTopicForm::class)->name('admin.question-topics.create');
        Route::get('/question-topics/{id}/edit', \App\Livewire\Admin\QuestionTopicForm::class)->name('admin.question-topics.edit');
        Route::get('/questions', \App\Livewire\Admin\Questions::class)->name('admin.questions');
        Route::get('/questions/create', \App\Livewire\Admin\QuestionForm::class)->name('admin.questions.create');
        Route::get('/questions/{id}/edit', \App\Livewire\Admin\QuestionForm::class)->name('admin.questions.edit');
        Route::get('/question-imports', \App\Livewire\Admin\QuestionImports::class)->name('admin.question-imports');
        Route::get('/question-imports/create', \App\Livewire\Admin\QuestionImportForm::class)->name('admin.question-imports.create');
        Route::get('/question-imports/{id}/edit', \App\Livewire\Admin\QuestionImportForm::class)->name('admin.question-imports.edit');
        Route::get('/exams', \App\Livewire\Admin\Exams::class)->name('admin.exams');
        Route::get('/exams/create', \App\Livewire\Admin\ExamForm::class)->name('admin.exams.create');
        Route::get('/exams/{id}/edit', \App\Livewire\Admin\ExamForm::class)->name('admin.exams.edit');
        Route::get('/topic-tests', \App\Livewire\Admin\TopicTests::class)->name('admin.topic-tests');
        Route::get('/topic-tests/create', \App\Livewire\Admin\TopicTestForm::class)->name('admin.topic-tests.create');
        Route::get('/topic-tests/{id}/edit', \App\Livewire\Admin\TopicTestForm::class)->name('admin.topic-tests.edit');

        // İçerik Yönetimi
        Route::get('/content-categories', \App\Livewire\Admin\ContentCategories::class)->name('admin.content-categories');
        Route::get('/content-categories/create', \App\Livewire\Admin\ContentCategoryForm::class)->name('admin.content-categories.create');
        Route::get('/content-categories/{id}/edit', \App\Livewire\Admin\ContentCategoryForm::class)->name('admin.content-categories.edit');
        Route::get('/topics', \App\Livewire\Admin\Topics::class)->name('admin.topics');
        Route::get('/topics/create', \App\Livewire\Admin\TopicForm::class)->name('admin.topics.create');
        Route::get('/topics/{id}/edit', \App\Livewire\Admin\TopicForm::class)->name('admin.topics.edit');
        Route::get('/pages', \App\Livewire\Admin\Pages::class)->name('admin.pages');
        Route::get('/pages/create', \App\Livewire\Admin\PageForm::class)->name('admin.pages.create');
        Route::get('/pages/{id}/edit', \App\Livewire\Admin\PageForm::class)->name('admin.pages.edit');
        Route::get('/traffic-sign-categories', \App\Livewire\Admin\TrafficSignCategories::class)->name('admin.traffic-sign-categories');
        Route::get('/traffic-sign-categories/create', \App\Livewire\Admin\TrafficSignCategoryForm::class)->name('admin.traffic-sign-categories.create');
        Route::get('/traffic-sign-categories/{id}/edit', \App\Livewire\Admin\TrafficSignCategoryForm::class)->name('admin.traffic-sign-categories.edit');
        Route::get('/traffic-signs', \App\Livewire\Admin\TrafficSigns::class)->name('admin.traffic-signs');
        Route::get('/traffic-signs/create', \App\Livewire\Admin\TrafficSignForm::class)->name('admin.traffic-signs.create');
        Route::get('/traffic-signs/{id}/edit', \App\Livewire\Admin\TrafficSignForm::class)->name('admin.traffic-signs.edit');
        Route::get('/blog-categories', \App\Livewire\Admin\BlogCategories::class)->name('admin.blog-categories');
        Route::get('/blog-categories/create', \App\Livewire\Admin\BlogCategoryForm::class)->name('admin.blog-categories.create');
        Route::get('/blog-categories/{id}/edit', \App\Livewire\Admin\BlogCategoryForm::class)->name('admin.blog-categories.edit');
        Route::get('/blog-posts', \App\Livewire\Admin\BlogPosts::class)->name('admin.blog-posts');
        Route::get('/blog-posts/create', \App\Livewire\Admin\BlogPostForm::class)->name('admin.blog-posts.create');
        Route::get('/blog-posts/{id}/edit', \App\Livewire\Admin\BlogPostForm::class)->name('admin.blog-posts.edit');

        // Kullanıcı Yönetimi
        Route::get('/admin-roles', \App\Livewire\Admin\AdminRoles::class)->name('admin.admin-roles');
        Route::get('/admin-roles/{id}/edit', \App\Livewire\Admin\AdminRoleForm::class)->name('admin.admin-roles.edit');
        Route::get('/staff-users', \App\Livewire\Admin\StaffUsers::class)->name('admin.staff-users');
        Route::get('/staff-users/create', \App\Livewire\Admin\StaffUserForm::class)->name('admin.staff-users.create');
        Route::get('/staff-users/{id}/edit', \App\Livewire\Admin\StaffUserForm::class)->name('admin.staff-users.edit');
        Route::get('/users', \App\Livewire\Admin\Users::class)->name('admin.users');
        Route::get('/users/create', \App\Livewire\Admin\UserForm::class)->name('admin.users.create');
        Route::get('/users/{id}/edit', \App\Livewire\Admin\UserForm::class)->name('admin.users.edit');

        // Premium Yönetimi
        Route::get('/premium-plans', \App\Livewire\Admin\PremiumPlans::class)->name('admin.premium-plans');
        Route::get('/premium-plans/create', \App\Livewire\Admin\PremiumPlanForm::class)->name('admin.premium-plans.create');
        Route::get('/premium-plans/{id}/edit', \App\Livewire\Admin\PremiumPlanForm::class)->name('admin.premium-plans.edit');

        // Bildirim Yönetimi
        Route::get('/notification-campaigns', \App\Livewire\Admin\NotificationCampaigns::class)->name('admin.notification-campaigns');
        Route::get('/notification-campaigns/create', \App\Livewire\Admin\NotificationCampaignForm::class)->name('admin.notification-campaigns.create');
        Route::get('/notification-campaigns/{id}/edit', \App\Livewire\Admin\NotificationCampaignForm::class)->name('admin.notification-campaigns.edit');
        Route::get('/automation-rules', \App\Livewire\Admin\AutomationRules::class)->name('admin.automation-rules');
        Route::get('/automation-rules/create', \App\Livewire\Admin\AutomationRuleForm::class)->name('admin.automation-rules.create');
        Route::get('/automation-rules/{id}/edit', \App\Livewire\Admin\AutomationRuleForm::class)->name('admin.automation-rules.edit');

        // SEO Yönetimi
        Route::get('/sitemap-settings', \App\Livewire\Admin\SitemapSettings::class)->name('admin.sitemap-settings');
        Route::get('/seo-settings', \App\Livewire\Admin\SeoSettings::class)->name('admin.seo-settings');
        Route::get('/seo-settings/pages', \App\Livewire\Admin\SeoPages::class)->name('admin.seo-settings.pages');
        Route::get('/seo-settings/verification', \App\Livewire\Admin\SeoVerification::class)->name('admin.seo-verification');
        Route::get('/seo-redirects', \App\Livewire\Admin\SeoRedirects::class)->name('admin.seo-redirects');
        Route::get('/seo-redirects/create', \App\Livewire\Admin\SeoRedirectForm::class)->name('admin.seo-redirects.create');
        Route::get('/seo-redirects/{id}/edit', \App\Livewire\Admin\SeoRedirectForm::class)->name('admin.seo-redirects.edit');
        Route::get('/seo-not-found-logs', \App\Livewire\Admin\SeoNotFoundLogs::class)->name('admin.seo-not-found-logs');

        Route::get('/media', \App\Livewire\Admin\MediaManager::class)->name('admin.media');

        // Sistem Yönetimi
        Route::get('/integrations', \App\Livewire\Admin\Integrations::class)->name('admin.integrations');
        Route::get('/integrations/{id}/edit', \App\Livewire\Admin\IntegrationForm::class)->name('admin.integrations.edit');
        Route::get('/advertisements', \App\Livewire\Admin\Advertisements::class)->name('admin.advertisements');
        foreach (['desktop', 'mobile'] as $device) {
            Route::get('/advertisements/'.$device, \App\Livewire\Admin\Advertisements::class)->defaults('device', $device)->name('admin.advertisements.'.$device);
            Route::get('/advertisements/'.$device.'/create', \App\Livewire\Admin\AdvertisementForm::class)->defaults('device', $device)->name('admin.advertisements.'.$device.'.create');
            Route::get('/advertisements/'.$device.'/{id}/edit', \App\Livewire\Admin\AdvertisementForm::class)->defaults('device', $device)->name('admin.advertisements.'.$device.'.edit');
        }
        Route::get('/advertisements/create', \App\Livewire\Admin\AdvertisementForm::class)->name('admin.advertisements.create');
        Route::get('/advertisements/{id}/edit', \App\Livewire\Admin\AdvertisementForm::class)->name('admin.advertisements.edit');
        Route::get('/audit-logs', \App\Livewire\Admin\AuditLogs::class)->name('admin.audit-logs');
        Route::get('/contact-messages', \App\Livewire\Admin\ContactMessages::class)->name('admin.contact-messages');
        Route::get('/contact-messages/{id}', \App\Livewire\Admin\ContactMessageDetail::class)->name('admin.contact-messages.show');
        Route::get('/navigation-items', \App\Livewire\Admin\NavigationItems::class)->name('admin.navigation-items');
        foreach (['footer', 'apps'] as $groupMenu) {
            Route::get('/navigation-items/'.$groupMenu.'/columns/create', \App\Livewire\Admin\NavigationGroupForm::class)->defaults('menu', $groupMenu)->name('admin.navigation-items.'.$groupMenu.'.columns.create');
            Route::get('/navigation-items/'.$groupMenu.'/columns/{id}/edit', \App\Livewire\Admin\NavigationGroupForm::class)->defaults('menu', $groupMenu)->name('admin.navigation-items.'.$groupMenu.'.columns.edit');
        }
        foreach (array_keys(\App\Models\NavigationItem::ADMIN_MENUS) as $menu) {
            Route::get('/navigation-items/'.$menu, \App\Livewire\Admin\NavigationItems::class)->defaults('menu', $menu)->name('admin.navigation-items.'.$menu);
            Route::get('/navigation-items/'.$menu.'/create', \App\Livewire\Admin\NavigationItemForm::class)->defaults('menu', $menu)->name('admin.navigation-items.'.$menu.'.create');
            Route::get('/navigation-items/'.$menu.'/{id}/edit', \App\Livewire\Admin\NavigationItemForm::class)->defaults('menu', $menu)->name('admin.navigation-items.'.$menu.'.edit');
        }
        Route::get('/navigation-items/create', \App\Livewire\Admin\NavigationItemForm::class)->name('admin.navigation-items.create');
        Route::get('/navigation-items/{id}/edit', \App\Livewire\Admin\NavigationItemForm::class)->name('admin.navigation-items.edit');
        Route::get('/settings', \App\Livewire\Admin\Settings::class)->name('admin.settings');
    });
});


// Keep this after all system routes so managed pages cannot shadow them.
Route::get('/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('page.show');

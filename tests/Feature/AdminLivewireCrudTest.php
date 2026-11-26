<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminRoleForm;
use App\Livewire\Admin\AdvertisementForm;
use App\Livewire\Admin\AutomationRuleForm;
use App\Livewire\Admin\BlogCategoryForm;
use App\Livewire\Admin\BlogPostForm;
use App\Livewire\Admin\BlogPosts;
use App\Livewire\Admin\ContentCategoryForm;
use App\Livewire\Admin\ExamForm;
use App\Livewire\Admin\Exams;
use App\Livewire\Admin\IntegrationForm;
use App\Livewire\Admin\NavigationItemForm;
use App\Livewire\Admin\NotificationCampaignForm;
use App\Livewire\Admin\PageForm;
use App\Livewire\Admin\Pages;
use App\Livewire\Admin\PremiumPlanForm;
use App\Livewire\Admin\QuestionForm;
use App\Livewire\Admin\QuestionImportForm;
use App\Livewire\Admin\Questions;
use App\Livewire\Admin\QuestionTopicForm;
use App\Livewire\Admin\SeoRedirectForm;
use App\Livewire\Admin\StaffUserForm;
use App\Livewire\Admin\TopicForm;
use App\Livewire\Admin\Topics;
use App\Livewire\Admin\TopicTestForm;
use App\Livewire\Admin\TopicTests;
use App\Livewire\Admin\TrafficSignCategoryForm;
use App\Livewire\Admin\TrafficSignForm;
use App\Livewire\Admin\TrafficSigns;
use App\Livewire\Admin\UserForm;
use App\Models\BlogPost;
use App\Models\Exam;
use App\Models\Lecture;
use App\Models\Page;
use App\Models\Question;
use App\Models\TopicTest;
use App\Models\TrafficSign;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('is_admin', true)->first();
    }

    public function test_admin_can_delete_topics_via_livewire(): void
    {
        $this->actingAs($this->admin);
        $lecture = Lecture::first();
        $this->assertNotNull($lecture);

        Livewire::test(Topics::class)
            ->call('delete', $lecture->id);

        $this->assertDatabaseMissing('lectures', [
            'id' => $lecture->id,
        ]);
    }

    public function test_admin_can_delete_pages_via_livewire(): void
    {
        $this->actingAs($this->admin);
        $page = Page::first();
        $this->assertNotNull($page);

        Livewire::test(Pages::class)
            ->call('delete', $page->id);

        $this->assertDatabaseMissing('pages', [
            'id' => $page->id,
        ]);
    }

    public function test_admin_can_delete_blog_posts_via_livewire(): void
    {
        $this->actingAs($this->admin);
        $post = BlogPost::first();
        $this->assertNotNull($post);

        Livewire::test(BlogPosts::class)
            ->call('delete', $post->id);

        $this->assertDatabaseMissing('blog_posts', [
            'id' => $post->id,
        ]);
    }

    public function test_admin_can_delete_exams_via_livewire(): void
    {
        $this->actingAs($this->admin);
        $exam = Exam::first();
        $this->assertNotNull($exam);

        Livewire::test(Exams::class)
            ->call('delete', $exam->id);

        $this->assertDatabaseMissing('exams', [
            'id' => $exam->id,
        ]);
    }

    public function test_admin_can_delete_questions_via_livewire(): void
    {
        $this->actingAs($this->admin);
        $question = Question::first();
        $this->assertNotNull($question);

        Livewire::test(Questions::class)
            ->call('delete', $question->id);

        $this->assertDatabaseMissing('questions', [
            'id' => $question->id,
        ]);
    }

    public function test_admin_can_delete_topic_tests_via_livewire(): void
    {
        $this->actingAs($this->admin);
        $topicTest = TopicTest::first();
        $this->assertNotNull($topicTest);

        Livewire::test(TopicTests::class)
            ->call('delete', $topicTest->id);

        $this->assertDatabaseMissing('topic_tests', [
            'id' => $topicTest->id,
        ]);
    }

    public function test_admin_can_delete_traffic_signs_via_livewire(): void
    {
        $this->actingAs($this->admin);
        $sign = TrafficSign::first();
        $this->assertNotNull($sign);

        Livewire::test(TrafficSigns::class)
            ->call('delete', $sign->id);

        $this->assertDatabaseMissing('traffic_signs', [
            'id' => $sign->id,
        ]);
    }

    public function test_topic_form_full_page_edit_and_create(): void
    {
        $this->actingAs($this->admin);
        $lecture = Lecture::first();

        // Test editing on full page
        Livewire::test(TopicForm::class, ['id' => $lecture->id])
            ->assertSet('title', $lecture->title)
            ->set('title', 'Tam Sayfa Guncel Baslik')
            ->call('save')
            ->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseHas('lectures', [
            'id' => $lecture->id,
            'title' => 'Tam Sayfa Guncel Baslik',
        ]);

        // Test creating on full page
        Livewire::test(TopicForm::class)
            ->set('title', 'Sıfırdan Yeni Ders')
            ->set('slug', 'sifirdan-yeni-ders')
            ->set('content', '<p>Yeni ders metni...</p>')
            ->call('save')
            ->assertRedirect(route('admin.topics'));

        $this->assertDatabaseHas('lectures', [
            'slug' => 'sifirdan-yeni-ders',
            'title' => 'Sıfırdan Yeni Ders',
        ]);
    }

    public function test_page_form_full_page_edit_and_create(): void
    {
        $this->actingAs($this->admin);
        $page = Page::first();

        Livewire::test(PageForm::class, ['id' => $page->id])
            ->assertSet('title', $page->title)
            ->set('title', 'Tam Sayfa Guncel Sayfa')
            ->call('save')
            ->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Tam Sayfa Guncel Sayfa',
        ]);
    }

    public function test_blog_post_form_full_page_edit_and_create(): void
    {
        $this->actingAs($this->admin);
        $post = BlogPost::first();

        Livewire::test(BlogPostForm::class, ['id' => $post->id])
            ->assertSet('title', $post->title)
            ->set('title', 'Tam Sayfa Guncel Blog')
            ->call('save')
            ->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title' => 'Tam Sayfa Guncel Blog',
        ]);
    }

    public function test_exam_form_full_page_edit_and_create(): void
    {
        $this->actingAs($this->admin);
        $exam = Exam::first();

        Livewire::test(ExamForm::class, ['id' => $exam->id])
            ->assertSet('title', $exam->title)
            ->set('title', 'Tam Sayfa Guncel Sinav')
            ->call('save')
            ->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'Tam Sayfa Guncel Sinav',
        ]);
    }

    public function test_topic_test_form_full_page_edit_and_create(): void
    {
        $this->actingAs($this->admin);
        $topicTest = TopicTest::first();

        Livewire::test(TopicTestForm::class, ['id' => $topicTest->id])
            ->assertSet('title', $topicTest->title)
            ->set('title', 'Tam Sayfa Guncel Test')
            ->call('save')
            ->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseHas('topic_tests', [
            'id' => $topicTest->id,
            'title' => 'Tam Sayfa Guncel Test',
        ]);
    }

    public function test_traffic_sign_form_full_page_edit_and_create(): void
    {
        $this->actingAs($this->admin);
        $sign = TrafficSign::first();

        Livewire::test(TrafficSignForm::class, ['id' => $sign->id])
            ->assertSet('name', $sign->name)
            ->set('name', 'Tam Sayfa Guncel Levha')
            ->call('save')
            ->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseHas('traffic_signs', [
            'id' => $sign->id,
            'name' => 'Tam Sayfa Guncel Levha',
        ]);
    }

    public function test_question_form_full_page_edit_and_create(): void
    {
        $this->actingAs($this->admin);
        $question = Question::first();

        // 1. Test updating existing question on full page
        Livewire::test(QuestionForm::class, ['id' => $question->id])
            ->assertSet('question_text', $question->question_text)
            ->set('question_text', 'Tam Sayfada Duzenlenen Soru Metni')
            ->call('save')
            ->assertHasNoErrors()->assertNoRedirect();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Tam Sayfada Duzenlenen Soru Metni',
        ]);

        // 2. Test creating dynamic 5-option question (adding option E)
        Livewire::test(QuestionForm::class)
            ->set('question_text', '5 Şıklı Yeni Nesil Ehliyet Sorusu')
            ->call('addOption') // Adds option E
            ->assertCount('options', 5)
            ->set('options.0.text', 'A şıkkı cevabı')
            ->set('options.1.text', 'B şıkkı cevabı')
            ->set('options.2.text', 'C şıkkı cevabı')
            ->set('options.3.text', 'D şıkkı cevabı')
            ->set('options.4.text', 'E şıkkı cevabı')
            ->set('correct_option', 'E')
            ->call('save')
            ->assertRedirect(route('admin.questions'));

        $this->assertDatabaseHas('questions', [
            'question_text' => '5 Şıklı Yeni Nesil Ehliyet Sorusu',
            'correct_option' => 'E',
        ]);
        $this->assertSame('E şıkkı cevabı', \App\Models\Question::where('question_text', '5 Şıklı Yeni Nesil Ehliyet Sorusu')->firstOrFail()->options_list['E']);

        // 3. Test removing options (down to 2 options - e.g. Doğru / Yanlış)
        Livewire::test(QuestionForm::class)
            ->call('removeOption', 3) // Remove D
            ->call('removeOption', 2) // Remove C
            ->assertCount('options', 2)
            ->set('question_text', 'Doğru Yanlış Sorusu')
            ->set('options.0.text', 'Doğru')
            ->set('options.1.text', 'Yanlış')
            ->set('correct_option', 'B')
            ->call('save')
            ->assertRedirect(route('admin.questions'));

        $this->assertDatabaseHas('questions', [
            'question_text' => 'Doğru Yanlış Sorusu',
            'correct_option' => 'B',
        ]);
        $this->assertSame(['A' => 'Doğru', 'B' => 'Yanlış'], \App\Models\Question::where('question_text', 'Doğru Yanlış Sorusu')->firstOrFail()->options_list);
    }
}

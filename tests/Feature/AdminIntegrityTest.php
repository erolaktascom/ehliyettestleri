<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContactMessages;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\QuestionForm;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\StaffUserForm;
use App\Livewire\Admin\TopicForm;
use App\Livewire\Admin\ContentCategoryForm;
use App\Livewire\Admin\TrafficSignCategoryForm;
use App\Models\Lecture;
use App\Models\LectureCategory;
use App\Models\TrafficSignCategory;
use App\Models\ContactMessage;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

class AdminIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_lecture_category_icon_can_be_selected_and_reset_to_automatic(): void
    {
        $category = LectureCategory::create(['name' => 'İlk Yardım', 'slug' => 'ilk-yardim', 'icon' => 'folder']);
        $form = Livewire::test(ContentCategoryForm::class, ['id' => $category->id])
            ->assertSet('icon', null)->assertSee('Otomatik (kategori adına göre)')
            ->set('icon', 'invalid')->call('save')->assertHasErrors('icon')
            ->set('icon', 'parking')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $parking = trim(Blade::render('<x-student-icon name="parking" />'));
        $this->get(route('lectures.index'))->assertOk()->assertSee($parking, false);

        $form->set('icon', '')->call('save')->assertHasNoErrors();
        $this->assertNull($category->fresh()->icon);
        $this->get(route('lectures.index'))->assertOk()->assertDontSee($parking, false)
            ->assertSee(trim(Blade::render('<x-student-icon name="first-aid" />')), false);
    }

    public function test_traffic_sign_category_icon_is_saved_on_creation_and_can_return_to_automatic(): void
    {
        Livewire::test(TrafficSignCategoryForm::class)->set('name', 'Trafik Tanzim İşaretleri')
            ->set('icon', 'parking')->call('save')->assertHasNoErrors()->assertRedirect(route('admin.traffic-sign-categories'));
        $category = TrafficSignCategory::firstOrFail();
        $parking = trim(Blade::render('<x-student-icon name="parking" />'));
        $this->get(route('traffic-signs.index'))->assertOk()->assertSee($parking, false);

        Livewire::test(TrafficSignCategoryForm::class, ['id' => $category->id])
            ->assertSet('icon', 'parking')->set('icon', 'invalid')->call('save')->assertHasErrors('icon')
            ->set('icon', '')->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertNull($category->fresh()->icon);
        $this->get(route('traffic-signs.index'))->assertOk()->assertDontSee($parking, false)
            ->assertSee(trim(Blade::render('<x-student-icon name="restriction" />')), false);
    }

    public function test_removing_an_earlier_option_preserves_the_correct_answer_text(): void
    {
        Livewire::test(QuestionForm::class)
            ->set('options', [
                ['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki'],
                ['key' => 'C', 'text' => 'Doğru'], ['key' => 'D', 'text' => 'Dört'],
            ])->set('correct_option', 'C')->call('removeOption', 0)
            ->assertSet('correct_option', 'B')->assertSet('options.1.text', 'Doğru');
    }

    public function test_removing_the_correct_option_requires_a_new_answer_selection(): void
    {
        Livewire::test(QuestionForm::class)
            ->set('options', [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki'], ['key' => 'C', 'text' => 'Üç']])
            ->set('correct_option', 'B')->call('removeOption', 1)
            ->assertSet('correct_option', '')
            ->set('question_text', 'Soru metni?')->call('save')->assertHasErrors('correct_option');
    }

    public function test_duplicate_option_keys_are_rejected(): void
    {
        Livewire::test(QuestionForm::class)->set('question_text', 'Soru metni?')
            ->set('options', [['key' => 'A', 'text' => 'Bir'], ['key' => 'A', 'text' => 'İki']])
            ->call('save')->assertHasErrors('options.0.key');
    }

    public function test_admin_cannot_remove_own_admin_access_through_the_edit_form(): void
    {
        $user = auth()->user();
        Livewire::test(UserForm::class, ['id' => $user->id])->set('is_admin', false)
            ->call('save')->assertHasErrors('is_admin');
        $this->assertTrue($user->fresh()->is_admin);
    }

    public function test_user_forms_allow_updating_without_changing_the_password(): void
    {
        $user = auth()->user();
        $password = $user->password;
        Livewire::test(UserForm::class, ['id' => $user->id])->set('name', 'Yeni İsim')
            ->call('save')->assertHasNoErrors();
        Livewire::test(StaffUserForm::class, ['id' => $user->id])->set('name', 'Yeni Yönetici')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Yeni Yönetici', $user->fresh()->name);
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_renaming_a_lecture_slug_keeps_a_single_record(): void
    {
        $category = LectureCategory::create(['name' => 'Kategori', 'slug' => 'kategori']);
        $lecture = Lecture::create(['lecture_category_id' => $category->id, 'title' => 'Konu', 'slug' => 'eski']);
        Livewire::test(TopicForm::class, ['id' => $lecture->id])->set('slug', 'yeni')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('yeni', $lecture->fresh()->slug);
        $this->assertDatabaseCount('lectures', 1);
    }

    public function test_renaming_a_category_slug_keeps_a_single_record(): void
    {
        $category = LectureCategory::create(['name' => 'Kategori', 'slug' => 'eski']);
        Livewire::test(ContentCategoryForm::class, ['id' => $category->id])->set('slug', 'yeni')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('yeni', $category->fresh()->slug);
        $this->assertDatabaseCount('lecture_categories', 1);
    }

    public function test_dashboard_and_reports_count_only_published_exams(): void
    {
        Exam::create(['title' => 'Yayında', 'slug' => 'yayinda', 'is_published' => true]);
        Exam::create(['title' => 'Taslak', 'slug' => 'taslak', 'is_published' => false]);
        Livewire::test(Dashboard::class)->assertViewHas('publishedExams', 1);
        Livewire::test(Reports::class)->assertViewHas('publishedExams', 1);
    }

    private function message(): ContactMessage
    {
        return ContactMessage::create(['name' => 'Test', 'email' => 'test@example.com', 'subject' => 'Soru', 'message' => 'Mesaj içeriği']);
    }

    public function test_contact_message_filters_use_the_read_flag(): void
    {
        $message = $this->message();
        Livewire::test(ContactMessages::class)->set('statusFilter', 'unread')
            ->assertViewHas('messages', fn ($messages) => $messages->contains('id', $message->id));
        Livewire::test(ContactMessages::class)->set('statusFilter', 'read')
            ->assertViewHas('messages', fn ($messages) => $messages->isEmpty());
    }

    public function test_contact_message_has_a_full_detail_page_and_is_marked_read(): void
    {
        $message = $this->message();
        $this->get('/admin/contact-messages/'.$message->id)->assertOk()->assertSee($message->message);
        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_revoked_admin_cannot_replay_a_livewire_delete_action(): void
    {
        $message = $this->message();
        $response = $this->get('/admin/contact-messages')->assertOk();
        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $snapshot = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        auth()->user()->update(['is_admin' => false]);
        $this->postJson('/livewire/update', ['components' => [[
            'snapshot' => $snapshot, 'updates' => [],
            'calls' => [['path' => '', 'method' => 'delete', 'params' => [$message->id]]],
        ]]], ['X-Livewire' => 'true'])->assertForbidden();
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id]);
    }
}

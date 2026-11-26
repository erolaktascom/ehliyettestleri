<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamForm;
use App\Livewire\Admin\Exams;
use App\Models\Exam;
use App\Models\User;
use App\Support\AdminAccess;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFlashMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
    }

    private function assertSingleMessage(string $html, string $type, string $message): void
    {
        $this->assertSame(1, substr_count($html, 'data-admin-flash="'.$type.'"'));
        $this->assertSame(1, substr_count($html, e($message)));
    }

    public function test_every_panel_module_renders_each_flash_message_once(): void
    {
        $messages = ['success' => 'İşlem başarıyla tamamlandı.', 'error' => 'İşlem tamamlanamadı.', 'warning' => 'Bilgileri kontrol edin.'];
        foreach (array_keys(AdminAccess::modules()) as $module) {
            $response = $this->withSession($messages)->get(route('admin.'.$module))->assertOk();
            foreach ($messages as $type => $message) {
                $this->assertSingleMessage($response->getContent(), $type, $message);
            }
        }
    }

    public function test_inline_livewire_actions_still_display_a_single_message(): void
    {
        $exam = Exam::firstOrFail();
        $component = Livewire::test(Exams::class)->call('toggleActive', $exam->id)->assertHasNoErrors();
        $this->assertSingleMessage($component->html(), 'success', $exam->title.' yayım durumu güncellendi.');
        $component->call('toggleActive', $exam->id)->assertHasNoErrors();
        $this->assertSingleMessage($component->html(), 'success', $exam->title.' yayım durumu güncellendi.');
    }

    public function test_exam_save_redirect_displays_one_message(): void
    {
        Livewire::test(ExamForm::class)->set('title', 'Yeni Deneme')->set('slug', 'yeni-deneme')
            ->call('save')->assertHasNoErrors()->assertRedirect(route('admin.exams'));
        $response = $this->get(route('admin.exams'))->assertOk();
        $this->assertSingleMessage($response->getContent(), 'success', 'Test ve soru sırası kaydedildi.');
    }

    public function test_edit_save_stays_on_form_and_displays_one_message(): void
    {
        $exam = Exam::firstOrFail();
        $component = Livewire::test(ExamForm::class, ['id' => $exam->id])
            ->set('is_published', false)->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertFalse($exam->fresh()->is_published);
        $this->assertSingleMessage($component->html(), 'success', 'Test ve soru sırası kaydedildi.');
        $component->set('is_published', true)->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertTrue($exam->fresh()->is_published);
        $this->assertSingleMessage($component->html(), 'success', 'Test ve soru sırası kaydedildi.');
    }
}

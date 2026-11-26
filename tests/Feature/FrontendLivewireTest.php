<?php

namespace Tests\Feature;

use App\Livewire\ContactForm;
use App\Livewire\SaveQuestionButton;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FrontendLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_contact_page_contains_livewire_contact_form(): void
    {
        $response = $this->get('/iletisim');
        $response->assertStatus(200);
        $response->assertSeeLivewire('contact-form');
    }

    public function test_contact_form_submits_successfully_via_livewire(): void
    {
        Livewire::test(ContactForm::class)
            ->set('name', 'Ahmet Yılmaz')
            ->set('email', 'ahmet@example.com')
            ->set('phone', '05551234567')
            ->set('subject', 'Deneme Sınavları Hakkında')
            ->set('message', 'Sınav sorularının güncelliği hakkında bilgi almak istiyorum.')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ahmet Yılmaz',
            'email' => 'ahmet@example.com',
            'subject' => 'Deneme Sınavları Hakkında',
        ]);
    }

    public function test_save_question_button_toggles_saved_state_for_authenticated_student(): void
    {
        $student = User::where('is_admin', false)->first() ?? User::factory()->create();
        $question = Question::first();

        $this->actingAs($student);

        Livewire::test(SaveQuestionButton::class, ['questionId' => $question->id])
            ->assertSet('isSaved', false)
            ->call('toggleSave')
            ->assertSet('isSaved', true);

        $this->assertDatabaseHas('saved_questions', [
            'user_id' => $student->id,
            'question_id' => $question->id,
        ]);

        // Toggle again to remove
        Livewire::test(SaveQuestionButton::class, ['questionId' => $question->id])
            ->assertSet('isSaved', true)
            ->call('toggleSave')
            ->assertSet('isSaved', false);

        $this->assertDatabaseMissing('saved_questions', [
            'user_id' => $student->id,
            'question_id' => $question->id,
        ]);
    }
}

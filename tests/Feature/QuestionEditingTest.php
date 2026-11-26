<?php

namespace Tests\Feature;

use App\Livewire\Admin\QuestionForm;
use App\Models\{Question, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_edits_track_history_without_removed_difficulty(): void
    {
        $this->travelTo(now()->startOfMinute());
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $question = Question::create([
            'question_text' => 'İlk soru metni?', 'status' => 'Yayında',
            'type' => 'Çoktan Seçmeli', 'options' => [['key' => 'A', 'text' => 'Bir'], ['key' => 'B', 'text' => 'İki']], 'correct_option' => 'A',
        ]);
        $first = $question->first_published_at;
        $saved = $question->updated_at;
        $this->travel(1)->days();
        $question->refresh();
        $this->assertTrue($question->first_published_at->equalTo($first));
        $this->assertTrue($question->updated_at->equalTo($saved));
        $this->assertNull($question->content_updated_at);
        $form = Livewire::test(QuestionForm::class, ['id' => $question->id])
            ->assertDontSee('Zorluk Seviyesi')->assertDontSee('Sınavda Sorulur')
            ->call('save')->assertHasNoErrors()->assertNoRedirect();
        $this->assertNull($question->fresh()->content_updated_at);
        $form->set('question_text', '<p>Değiştirilen <strong>soru</strong> metni?</p>')
            ->call('save')->assertHasNoErrors()->assertNoRedirect()
            ->assertSee('İlk Yayınlanma')->assertSee($first->format('d.m.Y'))
            ->assertSee('Son Güncelleme')->assertSee(now()->format('d.m.Y'));
        $question->refresh();
        $this->assertSame('<p>Değiştirilen <strong>soru</strong> metni?</p>', $question->question_text);
        $this->assertTrue($question->first_published_at->equalTo($first));
        $this->assertTrue($question->content_updated_at->equalTo(now()));
        $edited = $question->content_updated_at;
        $this->assertEquals($admin->id, $question->last_edited_by);
        $this->travel(1)->days();
        Livewire::test(QuestionForm::class, ['id' => $question->id])
            ->assertSet('question_text', $question->question_text)->call('save')->assertHasNoErrors();
        $form->set('status', 'Taslak')->call('save')->assertHasNoErrors();
        $form->set('status', 'Yayında')->call('save')->assertHasNoErrors();
        $this->assertTrue($question->fresh()->content_updated_at->equalTo($edited));
        $this->assertTrue($question->fresh()->first_published_at->equalTo($first));
    }

    public function test_question_schema_uses_only_json_options_and_pivots(): void
    {
        foreach (['difficulty', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'exam_id', 'topic_test_id', 'order', 'image_url', 'media_type', 'question_topic_id'] as $column) {
            $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('questions', $column));
        }
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('questions', 'options'));
    }
}

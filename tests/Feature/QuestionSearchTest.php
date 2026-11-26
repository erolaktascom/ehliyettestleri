<?php

namespace Tests\Feature;

use App\Livewire\Admin\Questions;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_text_and_exact_id_with_optional_hash(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $question = Question::create(['question_text' => 'Kavşakta geçiş önceliği kime aittir?', 'options' => [['key' => 'A', 'text' => 'A'], ['key' => 'B', 'text' => 'B']], 'correct_option' => 'A']);
        Question::create(['question_text' => 'Motor yağı ne zaman kontrol edilir?', 'options' => [['key' => 'A', 'text' => 'A'], ['key' => 'B', 'text' => 'B']], 'correct_option' => 'B']);
        $component = Livewire::test(Questions::class);
        foreach (['Kavşakta', (string) $question->id, '#'.$question->id, '  #'.$question->id.'  '] as $search) {
            $component->set('search', $search)->assertViewHas('questions',
                fn ($results) => $results->total() === 1 && $results->first()->id === $question->id);
        }
        $component->set('search', '#999999')->assertViewHas('questions', fn ($results) => $results->isEmpty());
        $component->set('search', '  ')->assertViewHas('questions', fn ($results) => $results->total() === 2);
    }

    public function test_id_search_respects_category_filter(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $category = TopicCategory::create(['name' => 'Trafik', 'slug' => 'trafik']);
        $other = TopicCategory::create(['name' => 'Motor', 'slug' => 'motor']);
        $question = Question::create(['question_text' => 'Trafik sorusu?', 'topic_category_id' => $category->id, 'options' => [['key' => 'A', 'text' => 'A'], ['key' => 'B', 'text' => 'B']], 'correct_option' => 'A']);
        Livewire::test(Questions::class)->set('search', (string) $question->id)
            ->set('selectedTopic', $other->id)->assertViewHas('questions', fn ($results) => $results->isEmpty())
            ->set('selectedTopic', $category->id)
            ->assertViewHas('questions', fn ($results) => $results->total() === 1);
    }
}

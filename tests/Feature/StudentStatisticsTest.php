<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\TopicCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_progress_uses_real_question_categories_without_fabricated_counts(): void
    {
        $user = User::factory()->create();
        $category = TopicCategory::create(['name' => 'Trafik', 'slug' => 'trafik-ve-cevre']);
        $empty = TopicCategory::create(['name' => 'Boş', 'slug' => 'bos']);
        $exam = Exam::create(['title' => 'Sınav', 'slug' => 'sinav']);
        $question = $exam->questions()->create(['topic_category_id' => $category->id, 'question_text' => 'Soru?', 'options' => [['key' => 'A', 'text' => 'Bir']], 'correct_option' => 'A']);
        $attempt = ExamAttempt::create([
            'user_id' => $user->id, 'exam_id' => $exam->id, 'status' => 'completed',
            'started_at' => now()->subMinute(), 'expires_at' => now()->addMinutes(44),
            'completed_at' => now(), 'score' => 100, 'is_passed' => true,
        ]);
        $attempt->answers()->create(['question_id' => $question->id, 'selected_option' => 'A', 'is_correct' => true]);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('categoryStats', function ($stats) use ($category, $empty) {
                $stats = collect($stats)->keyBy(fn ($row) => $row['category']->id);
                return $stats[$category->id]['total_questions'] === 1
                    && $stats[$category->id]['correct_questions'] === 1
                    && (int) $stats[$category->id]['percent'] === 100
                    && $stats[$empty->id]['total_questions'] === 0;
            });
    }

    public function test_unfinished_attempts_do_not_lower_the_performance_average(): void
    {
        $user = User::factory()->create(['premium_until' => now()->addMonth()]);
        $exam = Exam::create(['title' => 'Sınav', 'slug' => 'sinav']);
        foreach (['completed', 'in_progress'] as $status) {
            ExamAttempt::create([
                'user_id' => $user->id, 'exam_id' => $exam->id, 'status' => $status,
                'started_at' => now(), 'expires_at' => now()->addMinutes(45),
                'score' => $status === 'completed' ? 80 : 0,
            ]);
        }
        $this->actingAs($user)->get(route('student.detailed-performance'))->assertOk()
            ->assertViewHas('averageScore', 80)->assertViewHas('totalAttempts', 1);
    }

    public function test_dashboard_does_not_invent_an_exam_date_or_success_for_a_new_student(): void
    {
        $user = User::factory()->create(['driving_exam_date'=>null]);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Sınav tarihini ekle')->assertSee('İlk sonucunla oluşacak')
            ->assertDontSee('24 Ekim 2026')->assertDontSee('8 gün kaldı')
            ->assertDontSee('Tüm yanlışlarını çözdün')->assertDontSee('Tüm alanlarda başarılı');
        $this->freezeTime();
        $user->update(['driving_exam_date'=>today()->addDays(5)]);
        $this->get(route('dashboard'))->assertOk()->assertSee($user->driving_exam_date->translatedFormat('d F Y'))
            ->assertSee('5 gün kaldı')->assertDontSee('Sınav tarihini ekle');
        $user->update(['driving_exam_date'=>today()]);
        $this->get(route('dashboard'))->assertSee('Bugün sınav günü');
        $user->update(['driving_exam_date'=>today()->subDay()]);
        $this->get(route('dashboard'))->assertSee('Sınav tarihi geçti');
    }

    public function test_recent_results_only_show_the_students_last_three_completed_studies(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $exam = Exam::create(['title' => 'Deneme', 'slug' => 'deneme']);
        $expected = [];
        for ($i = 0; $i < 4; $i++) {
            $attempt = ExamAttempt::create(['user_id' => $user->id, 'exam_id' => $exam->id,
                'status' => 'completed', 'score' => 70 + $i, 'started_at' => now()->subDays($i + 1),
                'expires_at' => now()->subDays($i + 1)->addMinutes(45), 'created_at' => now()->subDays($i + 1), 'completed_at' => now()->subDays($i + 1)]);
            if ($i < 3) $expected[] = $attempt->id;
        }
        foreach ([[$other->id, 'completed'], [$user->id, 'in_progress']] as [$userId, $status]) {
            ExamAttempt::create(['user_id' => $userId, 'exam_id' => $exam->id,
                'status' => $status, 'score' => 99, 'started_at' => now(), 'expires_at' => now()->addMinutes(45)]);
        }
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Son çalışmaların')
            ->assertViewHas('recentAttempts', fn ($rows) => $rows->pluck('id')->all() === $expected)
            ->assertSee(route('exams.result', $expected[0]), false);
    }
}

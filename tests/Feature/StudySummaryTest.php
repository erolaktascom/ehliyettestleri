<?php
namespace Tests\Feature;

use App\Models\{Exam, ExamAttempt, TopicCategory, TopicTest, User, TrafficSign, TrafficSignCategory, UserSignReview};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_totals_scope_published_sections_and_count_each_finished_test_once(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = TopicCategory::create(['name'=>'Kategori', 'slug'=>'kategori', 'is_active'=>true]);
        $hidden = TopicCategory::create(['name'=>'Kapalı', 'slug'=>'kapali', 'is_active'=>false]);
        $tests = collect();
        foreach (['normal', 'unfinished', 'other-user', 'animated', 'draft', 'hidden'] as $slug) {
            $tests[$slug] = TopicTest::create(['title'=>$slug, 'slug'=>$slug, 'topic_category_id'=>$slug === 'hidden' ? $hidden->id : $category->id,
                'section'=>$slug === 'animated' ? 'animated' : 'topic_tests', 'is_published'=>$slug !== 'draft']);
        }
        foreach (['normal', 'normal', 'unfinished', 'other-user', 'animated', 'draft', 'hidden'] as $slug) {
            ExamAttempt::create(['user_id'=>$slug === 'other-user' ? $other->id : $user->id, 'topic_test_id'=>$tests[$slug]->id,
                'status'=>$slug === 'unfinished' ? 'in_progress' : 'completed', 'started_at'=>now(), 'expires_at'=>now()->addHour()]);
        }
        // Retaking a finished test must not remove its previous completion from the summary.
        ExamAttempt::create(['user_id'=>$user->id, 'topic_test_id'=>$tests['normal']->id, 'status'=>'in_progress', 'started_at'=>now(), 'expires_at'=>now()->addHour()]);
        $this->get('/konu-testleri')->assertOk()->assertViewHas('totalStudyCount', 3)->assertViewHas('completedStudyCount', 0);
        $this->actingAs($user)->get('/konu-testleri')->assertOk()->assertViewHas('totalStudyCount', 3)->assertViewHas('completedStudyCount', 1);
        $this->get('/konu-testleri/kategori')->assertOk()->assertViewHas('completedStudyCount', 1)->assertSee('Toplam Test');
        $this->get('/animasyonlu-sorular')->assertOk()->assertViewHas('completedStudyCount', 1)->assertViewHas('tests', fn ($tests) => $tests->count() === 1);
    }

    public function test_exam_and_sign_headers_use_only_current_members_progress(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $exam = Exam::create(['title'=>'Deneme', 'slug'=>'deneme', 'is_published'=>true]);
        $draft = Exam::create(['title'=>'Taslak', 'slug'=>'taslak', 'is_published'=>false]);
        foreach ([$exam, $exam, $draft] as $row) ExamAttempt::create(['user_id'=>$user->id, 'exam_id'=>$row->id, 'status'=>'completed', 'started_at'=>now(), 'expires_at'=>now()->addHour()]);
        $response = $this->actingAs($user)->get('/deneme')->assertOk()->assertSee('Toplam Deneme')->assertSee('1 Deneme');
        $response->assertViewHas('userAttemptsByExam', fn ($rows) => $rows->where('status', 'completed')->count() === 1);
        $category = TrafficSignCategory::create(['name'=>'İşaretler', 'slug'=>'isaretler']);
        $sign = TrafficSign::create(['traffic_sign_category_id'=>$category->id, 'name'=>'Dur', 'code'=>'T1', 'meaning'=>'Dur']);
        UserSignReview::create(['user_id'=>$other->id, 'traffic_sign_id'=>$sign->id, 'status'=>'known']);
        $this->get('/trafik-isaretleri')->assertOk()->assertSee('Toplam İşaret')->assertSee('Öğrenilen')->assertSee('0 İşaret');
        UserSignReview::create(['user_id'=>$user->id, 'traffic_sign_id'=>$sign->id, 'status'=>'known']);
        $this->get('/trafik-isaretleri')->assertOk()->assertViewHas('categories', fn ($rows) => $rows->sum('known_count') === 1);
    }
}

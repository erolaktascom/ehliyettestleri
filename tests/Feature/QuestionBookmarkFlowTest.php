<?php
namespace Tests\Feature;

use App\Models\{Exam, ExamAttempt, Question, TopicCategory, TopicTest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBookmarkFlowTest extends TestCase
{
    use RefreshDatabase;

    private function question(): Question
    {
        return Question::create(['question_text'=>'Kaydedilecek soru', 'status'=>'Yayında',
            'options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']], 'correct_option'=>'A']);
    }

    public function test_save_and_remove_are_idempotent_and_scoped_to_the_member(): void
    {
        $question=$this->question();
        $one=User::factory()->create(); $two=User::factory()->create();
        $url=route('questions.toggle-save',$question);
        $this->actingAs($one);
        foreach(range(1,2) as $_) $this->postJson($url,['saved'=>true])->assertOk()->assertJson(['saved'=>true,'total_saved'=>1]);
        $this->assertDatabaseCount('saved_questions',1);
        $this->get(route('student.saved-questions'))->assertOk()->assertSee('Kaydedilecek soru')->assertSee('question-bookmark');
        $this->actingAs($two)->postJson($url,['saved'=>true])->assertOk();
        $this->actingAs($one);
        foreach(range(1,2) as $_) $this->postJson($url,['saved'=>false])->assertOk()->assertJson(['saved'=>false,'total_saved'=>0]);
        $this->assertDatabaseHas('saved_questions',['user_id'=>$two->id,'question_id'=>$question->id]);
        $this->get(route('student.saved-questions'))->assertOk()->assertSee('Henüz kayıtlı soru bulunmuyor.');
        $this->postJson($url,['saved'=>'invalid'])->assertUnprocessable();
    }

    public function test_guests_cannot_bookmark_and_drafts_cannot_be_added(): void
    {
        $question=$this->question(); $url=route('questions.toggle-save',$question);
        $this->postJson($url,['saved'=>true])->assertUnauthorized();
        $question->update(['status'=>'Taslak']);
        $this->actingAs(User::factory()->create())->postJson($url,['saved'=>true])->assertNotFound();
        $this->assertDatabaseCount('saved_questions',0);
    }

    public function test_normal_and_animated_solving_and_results_share_saved_state(): void
    {
        $this->actingAs(User::factory()->create());
        foreach(['topic_tests','animated'] as $section) {
            $category=TopicCategory::create(['name'=>$section,'slug'=>$section,'is_active'=>true]);
            $test=TopicTest::create(['title'=>$section,'slug'=>'test','topic_category_id'=>$category->id,'section'=>$section,'is_published'=>true]);
            $question=$this->question(); $test->questions()->attach($question->id,['position'=>1]);
            $this->postJson(route('questions.toggle-save',$question),['saved'=>true])->assertOk();
            $this->post(route('topic-tests.start',$test))->assertRedirect();
            $attempt=ExamAttempt::where('topic_test_id',$test->id)->sole();
            $this->get($test->publicUrl())->assertOk()->assertSee('question-bookmark')->assertViewHas('savedQuestionIds',fn($ids)=>in_array($question->id,$ids));
            $this->post(route('exams.finish',$attempt))->assertRedirect();
            $this->get(route('exams.result',$attempt))->assertOk()->assertSee('question-bookmark')->assertViewHas('savedQuestionIds',fn($ids)=>in_array($question->id,$ids));
        }
    }

    public function test_exam_page_uses_the_unchanged_server_deadline_after_reload(): void
    {
        $this->actingAs(User::factory()->create());
        $exam=Exam::create(['title'=>'Deneme','slug'=>'deneme','is_published'=>true,'order'=>1,'duration_minutes'=>45]);
        $exam->questions()->attach($this->question()->id,['position'=>1]);
        $this->post(route('exams.start',$exam));
        $attempt=ExamAttempt::sole(); $deadline=$attempt->expires_at->valueOf();
        $this->get($attempt->playUrl())->assertOk()->assertSee('expiresAt: '.$deadline,false)->assertSee('serverNow:',false)->assertSee('question-bookmark');
        $this->travel(2)->minutes();
        $this->get($attempt->playUrl())->assertOk()->assertSee('expiresAt: '.$deadline,false)->assertViewHas('remainingSeconds',fn($seconds)=>$seconds<=2580);
        $this->assertSame($deadline,$attempt->fresh()->expires_at->valueOf());
        $this->travelBack();
    }
}

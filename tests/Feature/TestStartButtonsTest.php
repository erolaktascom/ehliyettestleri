<?php
namespace Tests\Feature;

use App\Models\{Exam, ExamAttempt, TopicCategory, TopicTest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestStartButtonsTest extends TestCase
{
    use RefreshDatabase;

    private function topic(string $section): TopicTest
    {
        $category = TopicCategory::create(['name'=>$section, 'slug'=>$section, 'is_active'=>true]);
        $test = TopicTest::create(['title'=>'Başlık testi '.$section, 'slug'=>'baslik-testi', 'topic_category_id'=>$category->id, 'section'=>$section, 'is_published'=>true]);
        $test->questions()->create(['topic_category_id'=>$category->id, 'question_text'=>'Soru', 'status'=>'Yayında',
            'options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']], 'correct_option'=>'A']);
        return $test;
    }

    private function xpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return new \DOMXPath($dom);
    }

    public function test_whole_cards_submit_protected_start_forms(): void
    {
        $this->actingAs(User::factory()->create());
        $normal=$this->topic('topic_tests');
        $animated=$this->topic('animated');
        $exam=Exam::create(['title'=>'Başlık sınavı','slug'=>'baslik-sinavi','order'=>1,'is_published'=>true,'duration_minutes'=>45]);
        $exam->questions()->attach($normal->questions()->first()->id,['position'=>1]);
        foreach ([[$normal->listUrl(),'topic-test-start-'.$normal->id,route('topic-tests.start',$normal),$normal->publicUrl()],
            [$animated->listUrl(),'topic-test-start-'.$animated->id,route('topic-tests.start',$animated),$animated->publicUrl()],
            [route('exams.index'),'exam-start-'.$exam->id,route('exams.start',$exam),route('exams.play',$exam)]] as [$list,$formId,$action,$play]) {
            $response=$this->get($list)->assertOk();
            $dom=$this->xpath($response->getContent());
            $this->assertSame(1,$dom->query('//form[@id="'.$formId.'"]/button[@type="submit" and @class="study-card-action"]')->length);
            $forms=$dom->query('//form[@id="'.$formId.'"]');
            $this->assertSame(1,$forms->length);
            $this->assertSame('POST',strtoupper($forms->item(0)->getAttribute('method')));
            $this->assertSame($action,$forms->item(0)->getAttribute('action'));
            $this->assertSame(1,$dom->query('.//input[@name="_token"]',$forms->item(0))->length);
            $before=ExamAttempt::count();
            $this->post($action)->assertRedirect($play);
            $this->assertSame($before+1,ExamAttempt::count());
            $attempt=ExamAttempt::latest('id')->first();
            $this->get($play)->assertOk()->assertViewHas('attempt',fn($row)=>$row->id===$attempt->id);
        }
    }

    public function test_started_and_finished_cards_keep_resume_result_and_separate_repeat_actions(): void
    {
        $this->actingAs(User::factory()->create());
        foreach(['topic_tests','animated'] as $section) {
            $test=$this->topic($section);
            $this->post(route('topic-tests.start',$test));
            $attempt=ExamAttempt::where('topic_test_id',$test->id)->sole();
            $dom=$this->xpath($this->get($test->listUrl())->assertOk()->getContent());
            $this->assertSame(1,$dom->query('//a[@class="study-card-action" and @href="'.$attempt->playUrl().'"]')->length);
            $this->assertSame(0,$dom->query('//form[@id="topic-test-start-'.$test->id.'"]')->length);
            $this->post(route('exams.finish',$attempt));
            $dom=$this->xpath($this->get($test->listUrl())->assertOk()->getContent());
            $this->assertSame(1,$dom->query('//a[@class="study-card-action" and @href="'.route('exams.result',$attempt).'"]')->length);
            $repeat=$dom->query('//form[@class="study-card-repeat"]');
            $this->assertSame(1,$repeat->length);
            $this->assertSame(route('topic-tests.start',$test),$repeat->item(0)->getAttribute('action'));
            $this->assertSame(0,$dom->query('//a//button | //a//form')->length);
            $this->assertSame(1,ExamAttempt::where('topic_test_id',$test->id)->count());
        }
    }

    public function test_direct_empty_tests_return_to_their_list_without_creating_attempts(): void
    {
        $test=$this->topic('topic_tests');
        $test->questions()->detach();
        $exam=Exam::create(['title'=>'Boş sınav','slug'=>'bos-sinav','order'=>1,'is_published'=>true]);
        $this->get($test->publicUrl())->assertRedirect($test->listUrl())->assertSessionHas('error');
        $this->get(route('exams.play',$exam))->assertRedirect(route('exams.index'))->assertSessionHas('error');
        $this->assertDatabaseCount('exam_attempts',0);
    }
}

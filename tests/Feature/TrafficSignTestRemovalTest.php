<?php
namespace Tests\Feature;
use App\Models\{TopicCategory,TopicTest,Question,ExamAttempt,SeoSetting,SeoRouteSetting,TrafficSign};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrafficSignTestRemovalTest extends TestCase
{
    use RefreshDatabase;
    public function test_cleanup_removes_only_generated_tests_and_unshared_questions_and_preserves_learning(): void
    {
        $category=TopicCategory::create(['name'=>'Trafik ve Çevre','slug'=>'trafik-ve-cevre']);
        $normal=TopicTest::create(['title'=>'Normal','slug'=>'normal','topic_category_id'=>$category->id]);
        $generated=TopicTest::create(['title'=>'İşaret Testi','slug'=>'trafik-isaretleri-test-1','section'=>'traffic_signs','topic_category_id'=>$category->id]);
        $fields=['topic_category_id'=>$category->id,'question_text'=>'<p>“Dur” trafik işaretinin anlamı aşağıdakilerden hangisidir?</p>','options'=>[['key'=>'A','text'=>'Dur']], 'correct_option'=>'A'];
        $unused=Question::create($fields);
        $shared=Question::create($fields);
        $generated->questions()->attach([$unused->id=>['position'=>1],$shared->id=>['position'=>2]]);
        $normal->questions()->attach($shared->id,['position'=>1]);
        $attempt=ExamAttempt::create(['topic_test_id'=>$generated->id,'session_id'=>'remove','status'=>'in_progress','question_ids'=>[$unused->id],'started_at'=>now(),'expires_at'=>now()->addDay(),'total_questions'=>1]);
        $attempt->answers()->create(['question_id'=>$unused->id,'selected_option'=>'A']);
        $setting=SeoSetting::create(['sitemap_sections'=>['konu-testleri','animasyonlu-testler','trafik-isareti-testleri']]);
        SeoRouteSetting::create(['route_key'=>'traffic-signs.tests']);
        $signCount=TrafficSign::count();
        $migration=require database_path('migrations/2026_11_02_220000_remove_traffic_sign_tests.php');
        $migration->up();
        $this->assertDatabaseMissing('topic_tests',['id'=>$generated->id]);
        $this->assertDatabaseMissing('questions',['id'=>$unused->id]);
        $this->assertDatabaseMissing('exam_attempts',['id'=>$attempt->id]);
        $this->assertDatabaseMissing('exam_attempt_answers',['exam_attempt_id'=>$attempt->id]);
        $this->assertDatabaseHas('questions',['id'=>$shared->id]);
        $this->assertSame([$shared->id],$normal->questions()->pluck('questions.id')->all());
        $this->assertSame($signCount,TrafficSign::count());
        $this->assertSame(['konu-testleri','animasyonlu-testler'],$setting->fresh()->sitemap_sections);
        $this->assertDatabaseMissing('seo_route_settings',['route_key'=>'traffic-signs.tests']);
        $migration->up();
        $this->assertDatabaseHas('topic_tests',['id'=>$normal->id]);
    }
}
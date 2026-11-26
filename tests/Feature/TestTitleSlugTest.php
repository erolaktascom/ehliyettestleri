<?php
namespace Tests\Feature;
use App\Models\{TopicCategory,TopicTest,Exam,ExamAttempt,User,SeoRedirect};
use App\Livewire\Admin\{TopicTestForm,ExamForm};
use App\Support\{TestSlugs,AutomaticSlugRedirects};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class TestTitleSlugTest extends TestCase
{
    use RefreshDatabase;
    private function category(string $slug): TopicCategory { return TopicCategory::create(['name'=>$slug,'slug'=>$slug,'is_active'=>true]); }
    private function testIn(TopicCategory $category,string $section='topic_tests'): TopicTest
    {
        $test=TopicTest::create(['title'=>'Seviye 1','slug'=>'seviye-1','section'=>$section,'topic_category_id'=>$category->id,'is_published'=>true]);
        $test->questions()->create(['topic_category_id'=>$category->id,'question_text'=>'Soru '.$category->slug,'options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']],'correct_option'=>'A','status'=>'Yayında']);
        return $test;
    }
    public function test_same_slug_resolves_correct_category_and_section_for_reading_and_starting(): void
    {
        $this->actingAs(User::factory()->create());
        $a=$this->testIn($this->category('ilk-yardim'));
        $b=$this->testIn($this->category('trafik'));
        $c=$this->testIn($a->category,'animated');
        foreach([$a,$b,$c] as $test) {
            $this->get($test->publicUrl())->assertRedirect($test->publicUrl());
            $this->get($test->publicUrl())->assertOk()->assertViewHas('test',fn($row)=>$row->id===$test->id)
                ->assertSee('name="robots" content="noindex, nofollow"',false)->assertDontSee('rel="canonical"',false)->assertViewHas('attempt',fn($row)=>$row->topic_test_id===$test->id)
                ->assertViewHas('questions',fn($rows)=>$rows->pluck('id')->all()===$test->questions()->pluck('questions.id')->all());
        }
        $this->assertSame(3,ExamAttempt::count());
        $this->get('/konu-testleri/baska/seviye-1')->assertNotFound();
    }
    public function test_forms_generate_scoped_slugs_and_keep_existing_or_custom_addresses(): void
    {
        $this->actingAs(User::factory()->create(['is_admin'=>true]));
        $a=$this->testIn($this->category('ilk-yardim'));
        $b=$this->category('trafik');
        Livewire::test(TopicTestForm::class)->set('topic_category_id',$a->topic_category_id)->set('title','Seviye 1')->assertSet('slug','seviye-1-2')
            ->set('topic_category_id',$b->id)->assertSet('slug','seviye-1')->call('save')->assertHasNoErrors();
        Livewire::test(TopicTestForm::class)->set('topic_category_id',$a->topic_category_id)->set('title','Seviye 1')->set('slug','seviye-1')->call('save')->assertHasErrors('slug');
        Livewire::test(TopicTestForm::class,['id'=>$a->id])->set('title','Yeni Başlık')->assertSet('slug','seviye-1');
        Livewire::test(ExamForm::class)->set('title','Özel Deneme')->assertSet('slug','ozel-deneme')->set('slug','benim-adresim')->set('title','Yeni Başlık')->assertSet('slug','benim-adresim');
        Exam::create(['title'=>'Seviye 1','slug'=>'seviye-1']);
        Livewire::test(ExamForm::class)->set('title','Seviye 1')->assertSet('slug','seviye-1-2')->call('save')->assertHasNoErrors();
    }
    public function test_bulk_normalization_keeps_history_questions_and_internal_practice_exam(): void
    {
        $a=$this->testIn($this->category('ilk-yardim'));
        $a->update(['slug'=>'ilk-yardim-seviye-1']);
        $exam=Exam::create(['title'=>'Seviye 2','slug'=>'deneme-seviye-2','is_published'=>true,'order'=>2]);
        $internal=Exam::create(['title'=>'Hatalı Sorular Tekrar Denemesi','slug'=>'yanlis-sorular-tekrar-testi','is_published'=>false]);
        $before=$a->fresh()->getAttributes();
        $pivots=DB::table('question_topic_test')->get()->toJson();
        DB::transaction(fn()=>TestSlugs::normalizeExisting());
        $this->assertSame('seviye-1',$a->fresh()->slug);
        $this->assertSame('seviye-2',$exam->fresh()->slug);
        $this->assertSame('yanlis-sorular-tekrar-testi',$internal->fresh()->slug);
        foreach(['updated_at','content_updated_at','first_published_at','created_by','last_edited_by'] as $field) $this->assertSame($before[$field],$a->fresh()->getAttributes()[$field]);
        $this->assertSame($pivots,DB::table('question_topic_test')->get()->toJson());
        $this->get('/konu-testleri/ilk-yardim/ilk-yardim-seviye-1')->assertStatus(301)->assertRedirect('/konu-testleri/ilk-yardim/seviye-1');
        $this->get('/deneme/deneme-seviye-2')->assertStatus(301)->assertRedirect('/deneme/seviye-2');
        SeoRedirect::where('old_url','/konu-testleri/ilk-yardim/ilk-yardim-seviye-1')->delete();
        $this->get('/konu-testleri/ilk-yardim/ilk-yardim-seviye-1')->assertNotFound();
    }
    public function test_database_enforces_animation_uniqueness_across_categories(): void
    {
        $this->testIn($this->category('bir'),'animated');
        $two=$this->category('iki');
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->testIn($two,'animated');
    }
}
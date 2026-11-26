<?php
namespace Tests\Feature;

use App\Models\{Exam, ExamAttempt, Question, TopicTest, TopicCategory, User, NotificationCampaign, AutomationRule};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class SelectedAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rich_explanations_render_safely_and_result_uses_the_exam_passing_score(): void
    {
        $user = User::factory()->create();
        $exam = Exam::create(['title'=>'Özel baraj', 'slug'=>'ozel-baraj', 'passing_score'=>85, 'is_published'=>true]);
        $question = Question::create(['question_text'=>'Soru', 'status'=>'Yayında',
            'options'=>[['key'=>'A','text'=>'Bir'],['key'=>'B','text'=>'İki']], 'correct_option'=>'A',
            'explanation'=>'<p><strong>Açıklama</strong></p><ul><li>Madde</li></ul><script>alert(111)</script><img src="/storage/question-image/test.png" onerror="alert(222)">']);
        $exam->questions()->attach($question->id, ['position'=>1]);
        $attempt = ExamAttempt::create(['user_id'=>$user->id, 'exam_id'=>$exam->id, 'status'=>'completed',
            'started_at'=>now()->subMinutes(5), 'expires_at'=>now()->addMinutes(40), 'completed_at'=>now(), 'score'=>100, 'is_passed'=>true,
            'total_questions'=>1, 'correct_count'=>1, 'question_ids'=>[$question->id]]);
        $attempt->answers()->create(['question_id'=>$question->id, 'selected_option'=>'A', 'is_correct'=>true]);
        $user->savedQuestions()->create(['question_id'=>$question->id]);
        $this->actingAs($user);
        foreach ([route('exams.result', $attempt), route('student.saved-questions')] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('<strong>Açıklama</strong>', false)
                ->assertSee('<li>Madde</li>', false)->assertDontSee('&lt;strong&gt;', false)
                ->assertDontSee('alert(111)', false)->assertDontSee('alert(222)', false);
            if (str_contains($url, 'sonuc')) $response->assertSee('Geçme barajı: 85 Puan');
        }
    }

    public function test_question_counts_are_derived_after_attach_and_detach_without_duplicate_columns(): void
    {
        $category = TopicCategory::create(['name'=>'Kategori','slug'=>'kategori']);
        $question = Question::create(['question_text'=>'Soru', 'status'=>'Yayında']);
        foreach ([Exam::create(['title'=>'Sınav','slug'=>'sinav']), TopicTest::create(['title'=>'Test','slug'=>'test','topic_category_id'=>$category->id])] as $test) {
            $this->assertFalse(Schema::hasColumn($test->getTable(), 'question_count'));
            $test->questions()->attach($question->id, ['position'=>1]);
            $this->assertSame(1, $test->fresh()->question_count);
            $this->assertSame(1, $test->fresh()->loadCount('questions')->question_count);
            $test->questions()->detach();
            $this->assertSame(0, $test->fresh()->question_count);
        }
        foreach (['site_title','meta_description','canonical_base'] as $column) $this->assertFalse(Schema::hasColumn('seo_settings', $column));
    }

    public function test_cleanup_preserves_unknown_legacy_labels_and_derives_modern_labels_from_groups_and_channels(): void
    {
        // Simulate the schema immediately before this migration.
        Schema::table('seo_settings', function ($table) {
            $table->string('site_title')->nullable(); $table->text('meta_description')->nullable(); $table->string('canonical_base')->nullable();
        });
        foreach (['exams','topic_tests'] as $table) Schema::table($table, fn ($t) => $t->integer('question_count')->default(0));
        $legacy = NotificationCampaign::create(['title'=>'Eski','target'=>'Aktif Olmayanlar']);
        $modern = NotificationCampaign::create(['title'=>'Yeni','target_groups'=>['everyone']]);
        DB::table('notification_campaigns')->where('id', $modern->id)->update(['target'=>'Yanlış eski etiket']);
        $rule = AutomationRule::create(['name'=>'Kural','delivery_channels'=>['in_app','email']]);
        DB::table('automation_rules')->where('id', $rule->id)->update(['channels'=>'Eski etiket']);
        $dates = [$legacy->updated_at, $modern->updated_at, $rule->updated_at];
        $migration = require database_path('migrations/2026_11_03_190000_remove_unused_and_duplicate_fields.php');
        $migration->up();
        $this->assertSame('Aktif Olmayanlar', $legacy->fresh()->target);
        $this->assertSame(\App\Support\NotificationAudience::label(['everyone']), $modern->fresh()->target);
        $this->assertNull($modern->fresh()->getRawOriginal('target'));
        $this->assertNull($rule->fresh()->getRawOriginal('channels'));
        $this->assertStringNotContainsString('Eski etiket', $rule->fresh()->channels);
        $this->assertEquals($dates, [$legacy->fresh()->updated_at,$modern->fresh()->updated_at,$rule->fresh()->updated_at]);
    }
}

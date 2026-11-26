<?php

namespace Tests\Feature;

use App\Models\{BlogPost, Exam, Lecture, Question, TopicTest, User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LastContentEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_content_types_preserve_creator_and_record_only_the_actual_editor(): void
    {
        $this->seed(DatabaseSeeder::class);
        $creator = User::factory()->create(['name' => 'Mehmet Test', 'is_admin' => true, 'role_name' => 'Editör']);
        $editor = User::factory()->create(['name' => 'Ali Test', 'is_admin' => true]);
        foreach ([Question::class => 'questions', Exam::class => 'exams', TopicTest::class => 'topic-tests', BlogPost::class => 'blog-posts', Lecture::class => 'topics'] as $model => $route) {
            $this->actingAs($creator);
            $record = $model::firstOrFail()->replicate();
            if ($record->slug) $record->slug .= '-last-editor';
            $record->save();
            $this->assertNull($record->fresh()->last_edited_by);
            $this->assertStringNotContainsString('Son Düzenleyen', Blade::render('<x-admin.content-dates :record="$record" />', compact('record')));
            $this->actingAs($editor);
            $field = $model === Question::class ? 'question_text' : 'title';
            $record->update([$field => $record->{$field}.' güncellendi']);
            $this->assertEquals($creator->id, $record->fresh()->created_by);
            $this->assertSame('Mehmet Test', $record->fresh()->creator_name);
            $this->assertEquals($editor->id, $record->fresh()->last_edited_by);
            $this->assertSame('Ali Test', $record->fresh()->last_editor_name);
            $this->get(route('admin.'.$route.'.edit', $record->id))->assertOk()->assertSee('Son Düzenleyen')->assertSee('Ali Test');
            $date = $record->fresh()->content_updated_at;
            $this->actingAs($creator);
            $this->travel(1)->days();
            $record->save();
            $record->update($model === Question::class ? ['status' => 'Taslak'] : ['is_published' => false]);
            $this->assertEquals($editor->id, $record->fresh()->last_edited_by);
            $this->assertTrue($date->eq($record->fresh()->content_updated_at));
            $record->update([$field => $record->{$field}.' tekrar düzenlendi']);
            $this->assertEquals($creator->id, $record->fresh()->last_edited_by);
            $this->travelBack();
        }
    }

    public function test_editor_cannot_be_spoofed_and_the_name_survives_user_deletion(): void
    {
        $actor = User::factory()->create(['name' => 'Gerçek Düzenleyen', 'is_admin' => true]);
        $other = User::factory()->create(['is_admin' => true]);
        $this->actingAs($actor);
        $record = Exam::create(['title' => 'Test', 'slug' => 'test']);
        $record->last_edited_by = $other->id;
        $record->last_editor_name = 'Sahte İsim';
        $record->save();
        $this->assertNull($record->fresh()->last_edited_by);
        $record->last_edited_by = $other->id;
        $record->last_editor_name = 'Sahte İsim';
        $record->title = 'Gerçek değişiklik';
        $record->save();
        $this->assertEquals($actor->id, $record->fresh()->last_edited_by);
        $this->assertSame($actor->name, $record->fresh()->last_editor_name);
        $this->actingAs($other);
        $actor->delete();
        $this->assertNull($record->fresh()->last_edited_by);
        $this->assertSame('Gerçek Düzenleyen', $record->fresh()->last_editor_name);
    }

    public function test_draft_edits_are_attributed_and_unattributed_changes_do_not_keep_a_stale_editor(): void
    {
        $actor = User::factory()->create(['is_admin' => true]);
        $this->actingAs($actor);
        $record = Exam::create(['title' => 'Taslak', 'slug' => 'taslak', 'is_published' => false]);
        $record->update(['title' => 'Taslak düzenlendi']);
        $this->assertEquals($actor->id, $record->fresh()->last_edited_by);
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $record->update(['title' => 'Panel kullanıcısı olmadan değişiklik']);
        $this->assertNull($record->fresh()->last_edited_by);
        $this->assertNull($record->fresh()->last_editor_name);
    }
}

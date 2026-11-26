<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['exam_question' => 'exam', 'question_topic_test' => 'topic_test'] as $tableName => $parent) {
            Schema::create($tableName, function (Blueprint $table) use ($parent) {
                $table->foreignId($parent.'_id')->constrained()->cascadeOnDelete();
                $table->foreignId('question_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->primary([$parent.'_id', 'question_id']);
            });
        }
        Schema::table('exam_attempts', fn (Blueprint $table) => $table->json('question_ids')->nullable());

        DB::transaction(function () {
            foreach (['exam_question' => 'exam', 'question_topic_test' => 'topic_test'] as $pivot => $parent) {
                $positions = [];
                foreach (DB::table('questions')->whereNotNull($parent.'_id')->orderBy('order')->orderBy('id')->get() as $question) {
                    $parentId = $question->{$parent.'_id'};
                    $positions[$parentId] = ($positions[$parentId] ?? 0) + 1;
                    DB::table($pivot)->insert([$parent.'_id' => $parentId, 'question_id' => $question->id, 'position' => $positions[$parentId]]);
                }
            }
            $practiceId = DB::table('exams')->where('slug', 'yanlis-sorular-tekrar-testi')->value('id');
            foreach (DB::table('exam_attempts')->orderBy('id')->get() as $attempt) {
                if ($practiceId && $attempt->exam_id === $practiceId) {
                    $ids = DB::table('exam_attempt_answers')->where('exam_attempt_id', $attempt->id)->orderBy('id')->pluck('question_id');
                } elseif ($attempt->topic_test_id) {
                    $ids = DB::table('question_topic_test')->where('topic_test_id', $attempt->topic_test_id)->orderBy('position')->pluck('question_id');
                } else {
                    $ids = DB::table('exam_question')->where('exam_id', $attempt->exam_id)->orderBy('position')->pluck('question_id');
                }
                DB::table('exam_attempts')->where('id', $attempt->id)->update(['question_ids' => json_encode($ids->all())]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', fn (Blueprint $table) => $table->dropColumn('question_ids'));
        Schema::dropIfExists('question_topic_test');
        Schema::dropIfExists('exam_question');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'target_license_class')) {
                $table->string('target_license_class')->default('B Sınıfı Ehliyet')->after('role_name');
            }
            if (!Schema::hasColumn('users', 'driving_exam_date')) {
                $table->date('driving_exam_date')->nullable()->after('target_license_class');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('driving_exam_date');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('city');
            }
        });

        if (!Schema::hasTable('saved_questions')) {
            Schema::create('saved_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('question_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'question_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_questions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['target_license_class', 'driving_exam_date', 'city', 'birth_date']);
        });
    }
};

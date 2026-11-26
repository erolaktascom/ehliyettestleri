<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = ['questions', 'exams', 'topic_tests', 'lectures'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->timestamp('published_at')->nullable());
            DB::table($name)->whereNotNull('first_published_at')
                ->update(['published_at' => DB::raw('first_published_at')]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('published_at'));
        }
    }
};

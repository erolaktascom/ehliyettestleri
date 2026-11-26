<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('topic_tests',fn(Blueprint $table)=>$table->dropUnique('topic_tests_slug_unique'));
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // MySQL has no partial indexes. NULL generated values exclude other sections.
            Schema::table('topic_tests', function (Blueprint $table) {
                $table->unsignedBigInteger('category_slug_scope')->nullable()->virtualAs("CASE WHEN section = 'topic_tests' THEN topic_category_id ELSE NULL END");
                $table->string('animated_slug_scope')->nullable()->virtualAs("CASE WHEN section = 'animated' THEN slug ELSE NULL END");
                $table->unique(['category_slug_scope', 'slug'], 'topic_tests_category_slug_unique');
                $table->unique('animated_slug_scope', 'topic_tests_animated_slug_unique');
            });
        } else {
            DB::statement("CREATE UNIQUE INDEX topic_tests_category_slug_unique ON topic_tests(topic_category_id, slug) WHERE section = 'topic_tests'");
            DB::statement("CREATE UNIQUE INDEX topic_tests_animated_slug_unique ON topic_tests(slug) WHERE section = 'animated'");
        }
        DB::transaction(fn()=>\App\Support\TestSlugs::normalizeExisting());
    }
    public function down(): void
    {
        throw new RuntimeException('Farklı kategorilerde aynı slug kullanılabilir; önceki yapıya veritabanı yedeğinden dönün.');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite rebuilds tables when foreign keys are removed. Disable cascades
        // before entering the transaction, then validate every reference before commit.
        Schema::withoutForeignKeyConstraints(function () {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                // MySQL DDL commits implicitly; this branch is used by fresh MySQL installations.
                $this->consolidate();
                return;
            }
            DB::transaction(function () {
                $this->consolidate();
                if (DB::getDriverName() === 'sqlite' && DB::select('PRAGMA foreign_key_check')) {
                    throw new RuntimeException('Foreign key validation failed during schema cleanup.');
                }
            });
        });
    }

    private function consolidate(): void
    {
        // Query builder deliberately avoids publication, author and audit model events.
        foreach (DB::table('questions')->orderBy('id')->get() as $question) {
            $options = json_decode($question->options ?? 'null', true);
            if (!$options) {
                $options = [];
                foreach (range('a', 'e') as $letter) {
                    $text = $question->{'option_'.$letter};
                    if ($text !== null && $text !== '') $options[] = ['key' => strtoupper($letter), 'text' => $text];
                }
            }
            if (!is_array($options)) throw new RuntimeException("Invalid options on question {$question->id}");
            $keys = array_map(fn ($option, $index) => strtoupper(is_array($option)
                ? ($option['key'] ?? (is_string($index) ? $index : chr(65 + $index)))
                : (is_string($index) ? $index : chr(65 + $index))), $options, array_keys($options));
            if (!in_array(strtoupper($question->correct_option), $keys, true)) {
                throw new RuntimeException("Correct answer missing on question {$question->id}");
            }
            $html = $question->question_text;
            if (filled($question->image_url) && !str_contains($html, $question->image_url)) {
                if ($question->media_type === 'video') {
                    $html .= \App\Support\QuestionMedia::embed($question->image_url);
                } else {
                    if (!\App\Models\NavigationItem::safeUrl($question->image_url)) {
                        throw new RuntimeException("Unsafe legacy media on question {$question->id}");
                    }
                    $html .= '<p><img src="'.htmlspecialchars($question->image_url, ENT_QUOTES, 'UTF-8').'" alt="Soru görseli"></p>';
                }
            }
            DB::table('questions')->where('id', $question->id)->update([
                'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'question_text' => $html,
            ]);
        }

        // Preserve existing pivot positions; append only missing legacy links.
        foreach (['exam_question' => 'exam_id', 'question_topic_test' => 'topic_test_id'] as $pivot => $parentKey) {
            foreach (DB::table('questions')->whereNotNull($parentKey)->orderBy('order')->orderBy('id')->get() as $question) {
                $where = [$parentKey => $question->{$parentKey}, 'question_id' => $question->id];
                if (!DB::table($pivot)->where($where)->exists()) {
                    DB::table($pivot)->insert($where + ['position' => 1 + (int) DB::table($pivot)->where($parentKey, $question->{$parentKey})->max('position')]);
                }
            }
        }

        // Category IDs belong to different tables; match by slug, never by numeric ID.
        foreach (DB::table('question_topics')->get() as $old) {
            $used = DB::table('questions')->whereNull('topic_category_id')->where('question_topic_id', $old->id)->exists();
            if (!$used) continue;
            $id = DB::table('topic_categories')->where('slug', $old->slug)->value('id');
            if (!$id) $id = DB::table('topic_categories')->insertGetId([
                'name' => $old->name, 'slug' => $old->slug, 'description' => $old->description,
                'icon' => $old->icon, 'order' => $old->order, 'is_active' => $old->is_active,
                'created_at' => $old->created_at, 'updated_at' => $old->updated_at,
            ]);
            DB::table('questions')->whereNull('topic_category_id')->where('question_topic_id', $old->id)->update(['topic_category_id' => $id]);
        }

        Schema::table('lectures', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
        });
        $categoryMap = [];
        foreach (DB::table('content_categories')->orderBy('id')->get() as $old) {
            $id = DB::table('lecture_categories')->where('slug', $old->slug)->value('id');
            if (!$id) {
                $matches = DB::table('topics')->where('content_category_id', $old->id)
                    ->join('lectures', 'lectures.slug', '=', 'topics.slug')->distinct()->pluck('lectures.lecture_category_id');
                if ($matches->count() === 1) $id = $matches->first();
            }
            if (!$id) $id = DB::table('lecture_categories')->insertGetId([
                'name' => $old->name, 'slug' => $old->slug, 'description' => $old->description,
                'icon' => $old->icon, 'order' => $old->order, 'created_at' => $old->created_at, 'updated_at' => $old->updated_at,
            ]);
            $categoryMap[$old->id] = $id;
        }
        foreach (DB::table('topics')->orderBy('id')->get() as $old) {
            $existing = DB::table('lectures')->where('slug', $old->slug)->first();
            if ($existing && $existing->title === $old->title && ($existing->content ?? '') === ($old->content ?? '')
                && ($existing->summary ?? '') === ($old->short_description ?? '')) {
                DB::table('lectures')->where('id', $existing->id)->update([
                    'view_count' => max($existing->view_count, $old->view_count),
                    'seo_title' => $existing->seo_title ?: $old->seo_title,
                    'seo_description' => $existing->seo_description ?: $old->seo_description,
                ]);
                continue;
            }
            $categoryId = $categoryMap[$old->content_category_id] ?? null;
            if (!$categoryId) {
                $categoryId = DB::table('lecture_categories')->where('slug', 'aktarilan-dersler')->value('id')
                    ?: DB::table('lecture_categories')->insertGetId(['name' => 'Aktarılan Dersler', 'slug' => 'aktarilan-dersler', 'order' => 999]);
            }
            $slug = $existing ? $old->slug.'-eski-'.$old->id : $old->slug;
            while (DB::table('lectures')->where('slug', $slug)->exists()) $slug .= '-aktarim';
            DB::table('lectures')->insert([
                'lecture_category_id' => $categoryId, 'title' => $old->title, 'slug' => $slug,
                'summary' => $old->short_description, 'content' => $old->content, 'is_published' => false,
                'order' => 1 + (int) DB::table('lectures')->where('lecture_category_id', $categoryId)->max('order'),
                'view_count' => $old->view_count, 'seo_title' => $old->seo_title, 'seo_description' => $old->seo_description,
                'published_at' => $old->published_at, 'publication_history_unknown' => true,
                'created_at' => $old->created_at, 'updated_at' => $old->updated_at,
            ]);
        }

        foreach (DB::table('advertisements')->whereNotNull('image')->get() as $ad) {
            if (filled($ad->code) || blank($ad->image)) continue;
            if (!\App\Models\NavigationItem::safeUrl($ad->image)) throw new RuntimeException("Unsafe advertisement image {$ad->id}");
            DB::table('advertisements')->where('id', $ad->id)->update([
                'code' => '<img src="'.htmlspecialchars($ad->image, ENT_QUOTES, 'UTF-8').'" alt="'.htmlspecialchars($ad->title, ENT_QUOTES, 'UTF-8').'">',
            ]);
        }
        // Empty in the live DB, but preserve old blog images if migrating another installation.
        foreach (DB::table('blog_posts')->whereNotNull('image_url')->get() as $post) {
            if (blank($post->image_url) || str_contains($post->content ?? '', $post->image_url)) continue;
            if (!\App\Models\NavigationItem::safeUrl($post->image_url)) throw new RuntimeException("Unsafe blog image {$post->id}");
            DB::table('blog_posts')->where('id', $post->id)->update(['content' => '<p><img src="'.htmlspecialchars($post->image_url, ENT_QUOTES, 'UTF-8').'"></p>'.$post->content]);
        }
        // Do not silently discard any old nonempty integration configuration.
        if (DB::table('integrations')->whereNotNull('settings')->whereNotIn('settings', ['', '[]', '{}', 'null'])->exists()) {
            throw new RuntimeException('Legacy integration settings must be migrated before schema cleanup.');
        }

        Schema::table('questions', function (Blueprint $table) {
            foreach (['exam_id', 'topic_test_id', 'question_topic_id'] as $key) $table->dropForeign([$key]);
            $table->dropColumn(['exam_id', 'topic_test_id', 'question_topic_id', 'order', 'difficulty', 'image_url', 'media_type', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e']);
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['question_topic_id']);
            $table->dropColumn(['mode', 'is_public', 'is_shuffle', 'question_topic_id']);
        });
        foreach ([
            'topic_tests' => ['duration_minutes'], 'advertisements' => ['target_url', 'image'],
            'integrations' => ['settings'], 'blog_posts' => ['image_url'],
            'navigation_items' => ['is_web', 'is_mobile', 'is_footer'],
            'admin_roles' => ['permissions_count', 'is_super_admin', 'is_system'],
        ] as $name => $columns) Schema::table($name, fn (Blueprint $table) => $table->dropColumn($columns));
        Schema::drop('question_topics');
        Schema::drop('topics');
        Schema::drop('content_categories');
    }

    public function down(): void
    {
        throw new RuntimeException('This data consolidation is irreversible. Restore the verified pre-migration database and matching code backup.');
    }
};

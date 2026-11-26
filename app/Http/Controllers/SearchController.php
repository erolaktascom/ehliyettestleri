<?php

namespace App\Http\Controllers;

use App\Models\{BlogPost, Exam, Lecture, Page, TopicTest};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $input = $request->query('q', '');
        $query = is_string($input) ? mb_substr(trim($input), 0, 100) : '';
        $results = null;

        if ($query !== '') {
            // Escape LIKE wildcards so a search for % or _ does not list every record.
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query).'%';
            $sources = [
                [Exam::where('is_published', true), 'exams', 'exam', null],
                [TopicTest::where('is_published', true)->whereHas('category', fn ($q) => $q->where('is_active', true))
                    ->join('topic_categories', 'topic_categories.id', '=', 'topic_tests.topic_category_id'), 'topic_tests', 'test', 'topic_categories'],
                [Lecture::where('is_published', true)->join('lecture_categories', 'lecture_categories.id', '=', 'lectures.lecture_category_id'), 'lectures', 'lecture', 'lecture_categories'],
                [BlogPost::visible(), 'blog_posts', 'blog', null],
                [Page::where('status', 'published'), 'pages', 'page', null],
            ];
            $union = null;
            foreach ($sources as [$builder, $table, $type, $category]) {
                $part = $builder->whereRaw("{$table}.title LIKE ? ESCAPE '!'", [$pattern])
                    ->select(["{$table}.id", "{$table}.title", "{$table}.slug"])
                    ->selectRaw('? AS type', [$type])
                    ->selectRaw($table === 'topic_tests' ? 'topic_tests.section AS section' : 'NULL AS section')
                    ->selectRaw($category ? "{$category}.slug AS category_slug, {$category}.name AS category_name" : 'NULL AS category_slug, NULL AS category_name')->toBase();
                $union = $union ? $union->unionAll($part) : $part;
            }
            $results = DB::query()->fromSub($union, 'results')->orderBy('title')->orderBy('type')->orderBy('id')
                ->paginate(12)->appends(['q' => $query]);
            $results->through(function ($result) {
                [$result->label, $result->url] = match ($result->type) {
                    'exam' => ['Deneme Sınavı', route('exams.play', ['exam' => $result->slug])],
                    'test' => [TopicTest::SECTIONS[$result->section], match ($result->section) {
                        'animated' => route('animated-questions.play', ['test' => $result->slug]),
                        default => route('topic-tests.play', ['category' => $result->category_slug, 'test' => $result->slug]),
                    }],
                    'lecture' => ['Ders Konusu', route('lectures.show', ['category' => $result->category_slug, 'lecture' => $result->slug])],
                    'blog' => ['Blog Yazısı', route('blog.show', ['post' => $result->slug])],
                    'page' => ['Sayfa', route('page.show', ['slug' => $result->slug])],
                };
                return $result;
            });
        }

        return view('pages.search', compact('query', 'results'));
    }
}

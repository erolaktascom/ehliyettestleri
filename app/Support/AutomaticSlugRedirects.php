<?php
namespace App\Support;
use App\Models\{SeoRedirect, BlogPost, BlogCategory, Page, Lecture, LectureCategory, TopicCategory, TrafficSignCategory, Exam, TopicTest};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
class AutomaticSlugRedirects {
    public static function publicPath(Model $model): ?string { return self::path($model); }
    private static function path(Model $model, bool $original=false): ?string {
        $slug=rawurlencode((string)($original ? $model->getRawOriginal('slug') : $model->slug));
        return match (true) {
            $model instanceof BlogPost => '/blog/'.$slug,
            $model instanceof BlogCategory => '/kategori/'.$slug,
            $model instanceof Page => '/'.$slug,
            $model instanceof TopicCategory => '/konu-testleri/'.$slug,
            $model instanceof TrafficSignCategory => '/ogren/trafik-isaretleri/'.$slug,
            $model instanceof LectureCategory => '/konu/'.$slug,
            $model instanceof Lecture => self::lecturePath($model,$slug,$original),
            $model instanceof Exam => '/deneme/'.$slug,
            $model instanceof TopicTest => self::topicTestPath($model,$slug,$original),
            default => null,
        };
    }
    private static function lecturePath(Lecture $model,string $slug,bool $original): ?string {
        $category=LectureCategory::find($original ? $model->getRawOriginal('lecture_category_id') : $model->lecture_category_id);
        return $category ? '/konu/'.rawurlencode($category->slug).'/'.$slug : null;
    }
    private static function topicTestPath(TopicTest $model,string $slug,bool $original): ?string {
        $category=TopicCategory::find($original ? $model->getRawOriginal('topic_category_id') : $model->topic_category_id);
        $section = $original ? $model->getRawOriginal('section') : $model->section;
        if ($section === 'animated') return '/animasyonlu-sorular/'.$slug;
        return $category ? '/konu-testleri/'.rawurlencode($category->slug).'/'.$slug : null;
    }
    private static function owns(SeoRedirect $rule, Model $model): bool {
        return $rule->source_model === $model::class && (int)$rule->source_id === (int)$model->id;
    }
    private static function exactSource(SeoRedirect $rule, string $path): bool {
        foreach ($rule->sourceEntries() as $source) {
            if (($source['match_type'] ?? 'exact') !== 'exact') continue;
            try { $url=SeoRedirectRules::normalize($source['url'],true); }
            catch (\InvalidArgumentException) { continue; }
            if (!empty($source['ignore_case']) ? mb_strtolower($url) === mb_strtolower($path) : $url === $path) return true;
        }
        return false;
    }
    public static function assertAvailable(Model $model): void {
        $path=self::path($model); if (!$path) return;
        self::releaseDestination($path,$model);
    }
    private static function releaseDestination(string $path,Model $model): void {
        foreach (SeoRedirect::all() as $rule) {
            if (!self::exactSource($rule,$path)) continue;
            if (self::owns($rule,$model)) $rule->delete();
            elseif (in_array($rule->status,['Aktif','active'])) {
                throw ValidationException::withMessages(['slug'=>'Bu adres için etkin yönlendirme var. Önce Yönlendirmeler bölümündeki kaydı kaldırın.']);
            }
        }
    }
    public static function changed(Model $model): void {
        if (!$model->wasChanged('slug') && !($model instanceof Lecture && $model->wasChanged('lecture_category_id'))
            && !($model instanceof TopicTest && ($model->wasChanged('topic_category_id') || $model->wasChanged('section')))) return;
        $old=self::path($model,true); $new=self::path($model);
        if (!$old || !$new || $old === $new) return;
        self::move($old,$new,$model);
        if ($model instanceof LectureCategory) {
            foreach ($model->lectures()->get() as $lecture) {
                self::move($old.'/'.rawurlencode($lecture->slug),$new.'/'.rawurlencode($lecture->slug),$lecture);
            }
        }
        if ($model instanceof TopicCategory) {
            foreach ($model->topicTests()->where('section', 'topic_tests')->get() as $test) {
                self::move($old.'/'.rawurlencode($test->slug),$new.'/'.rawurlencode($test->slug),$test);
            }
        }
    }
    public static function migratePagePath(Page $page): void {
        self::move('/sayfa/'.rawurlencode($page->slug), '/'.rawurlencode($page->slug), $page);
    }
    private static function move(string $old,string $new,Model $model): void {
        $old=SeoRedirectRules::normalize($old,true); $new=SeoRedirectRules::normalize($new,true);
        self::releaseDestination($new,$model);
        // Keep existing automatic histories direct (A->C), without recreating deleted entries.
        foreach (SeoRedirect::where('source_model',$model::class)->where('source_id',$model->id)->where('new_url',$old)->get() as $rule) {
            $rule->new_url=$new; $rule->save();
        }
        foreach (SeoRedirect::all() as $rule) {
            // A manual rule always remains under the administrator's control.
            if (self::exactSource($rule,$old)) return;
        }
        $data=SeoRedirectRules::validate(['sources'=>[['url'=>$old,'match_type'=>'exact','ignore_case'=>false]],
            'new_url'=>$new,'type'=>'301','status'=>'Aktif'],null);
        $rule=new SeoRedirect($data);
        $rule->source_model=$model::class; $rule->source_id=$model->id; $rule->save();
    }
}

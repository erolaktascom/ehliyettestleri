<?php
namespace App\Support;
use App\Models\{Exam,TopicTest};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TestSlugs
{
    public static function topicRule(string $section, ?int $category, ?int $ignore=null): \Illuminate\Validation\Rules\Unique
    {
        return Rule::unique('topic_tests','slug')->ignore($ignore)->where(function($query) use($section,$category) {
            $query->where('section',$section);
            if($section==='topic_tests') $query->where('topic_category_id',$category);
        });
    }

    public static function generate(Exam|TopicTest $record, string $title): string
    {
        $base=Str::slug($title) ?: 'test';
        $base=mb_substr($base,0,230);
        $rules=SeoRedirectRules::activeRules();
        for($number=1;$number<=1000;$number++) {
            $slug=$base.($number>1?'-'.$number:'');
            $query=$record->newQuery()->where('slug',$slug);
            if($record->exists) $query->whereKeyNot($record->id);
            if($record instanceof TopicTest) {
                $query->where('section',$record->section);
                if($record->section==='topic_tests') $query->where('topic_category_id',$record->topic_category_id);
            }
            if($query->exists()) continue;
            $candidate=clone $record; $candidate->slug=$slug;
            $path=AutomaticSlugRedirects::publicPath($candidate);
            $match=$path?SeoRedirectRules::resolve($path,$rules):null;
            if($match && !($record->exists && $match[0]->source_model===$record::class && (int)$match[0]->source_id===(int)$record->id)) continue;
            return $slug;
        }
        throw ValidationException::withMessages(['slug'=>'Bu başlık için boş adres bulunamadı. Yönlendirme kurallarını kontrol edin.']);
    }

    public static function normalizeExisting(): int
    {
        $changed=0;
        foreach([Exam::class,TopicTest::class] as $class) {
            foreach($class::orderBy('id')->get() as $record) {
                // This private practice exam is also a stable internal discriminator.
                if($record instanceof Exam && $record->slug==='yanlis-sorular-tekrar-testi') continue;
                $slug=self::generate($record,$record->title);
                if($slug===$record->slug) continue;
                $old=AutomaticSlugRedirects::publicPath($record);
                \Illuminate\Support\Facades\DB::table($record->getTable())->where('id',$record->id)->update(['slug'=>$slug]);
                $record->slug=$slug; $record->syncChanges();
                AutomaticSlugRedirects::changed($record);
                $new=AutomaticSlugRedirects::publicPath($record);
                // Update direct menu links without altering their titles, order or visibility.
                foreach(\App\Models\NavigationItem::all() as $item) {
                    $parts=parse_url($item->url);
                    if(!$parts || ($parts['path']??'')!==$old) continue;
                    if(isset($parts['host']) && !in_array($parts['host'],[parse_url(config('app.url'),PHP_URL_HOST),'localhost','127.0.0.1'],true)) continue;
                    $url=str_replace($old,$new,$item->url);
                    \Illuminate\Support\Facades\DB::table('navigation_items')->where('id',$item->id)->update(['url'=>$url]);
                }
                $changed++;
            }
        }
        return $changed;
    }
}
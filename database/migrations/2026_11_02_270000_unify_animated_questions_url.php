<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $old='/animasyonlu-ehliyet-sorulari'; $new='/animasyonlu-sorular';
            $hosts=array_filter([parse_url(config('app.url'),PHP_URL_HOST),'localhost','127.0.0.1']);
            $local=function($url)use($old,$hosts){$parts=parse_url($url);return $parts && (!isset($parts['host']) || in_array(strtolower($parts['host']),$hosts,true)) && rtrim($parts['path']??'','/')===$old;};
            foreach(DB::table('navigation_items')->get() as $item){
                if($local($item->url)) DB::table('navigation_items')->where('id',$item->id)->update(['url'=>preg_replace('~/animasyonlu-ehliyet-sorulari/?(?=[?#]|$)~',$new,$item->url)]);
            }
            $setting=DB::table('seo_route_settings')->where('route_key','animated-questions.index')->first();
            if($setting && $setting->seo_canonical && $local($setting->seo_canonical)) DB::table('seo_route_settings')->where('id',$setting->id)->update(['seo_canonical'=>null]);
            if (!config('installation.fresh', false) && !DB::table('seo_redirects')->where('old_url',$old)->exists()) DB::table('seo_redirects')->insert([
                'old_url'=>$old,'new_url'=>$new,'type'=>'301','status'=>'Aktif',
                'sources'=>json_encode([['url'=>$old,'match_type'=>'exact','ignore_case'=>false]]),
                'usage_count'=>0,'created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }
    public function down(): void { /* Restore the pre-migration backup if needed. */ }
};
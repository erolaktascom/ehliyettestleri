<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $hosts=array_filter([parse_url(config('app.url'),PHP_URL_HOST),parse_url(DB::table('seo_settings')->value('canonical_base')?:'',PHP_URL_HOST),'localhost','127.0.0.1']);
            foreach(DB::table('navigation_items')->get() as $item) {
                $parts=parse_url($item->url);
                if(!$parts || (isset($parts['host'])&&!in_array($parts['host'],$hosts,true))) continue;
                if(rtrim($parts['path']??'','/')!=='/ogren/trafik-isaretleri') continue;
                DB::table('navigation_items')->where('id',$item->id)->update([
                    'url'=>preg_replace('~/ogren/trafik-isaretleri/?(?=[?#]|$)~','/trafik-isaretleri',$item->url)
                ]);
            }
            $old=DB::table('seo_route_settings')->where('route_key','traffic-signs.categories')->first();
            if($old) {
                $target=DB::table('seo_route_settings')->where('route_key','traffic-signs.index')->first();
                $changes=[];
                foreach(['seo_title','seo_description','seo_canonical'] as $field) {
                    if(trim((string)($target->$field??''))==='' && trim((string)$old->$field)!=='') $changes[$field]=$old->$field;
                }
                if(($target->seo_index??'inherit')==='inherit') $changes['seo_index']=$old->seo_index;
                // A self-canonical must follow the moved listing, while external overrides remain intact.
                $canonical=$changes['seo_canonical']??($target->seo_canonical??null);
                if($canonical && in_array(parse_url($canonical,PHP_URL_HOST),$hosts,true) && rtrim(parse_url($canonical,PHP_URL_PATH)??'','/')==='/ogren/trafik-isaretleri') $changes['seo_canonical']=null;
                if($target) {
                    if($changes) DB::table('seo_route_settings')->where('id',$target->id)->update($changes);
                } else {
                    DB::table('seo_route_settings')->insert(['route_key'=>'traffic-signs.index','created_at'=>now(),'updated_at'=>now(),...$changes]);
                }
                DB::table('seo_route_settings')->where('id',$old->id)->delete();
            }
            if (!config('installation.fresh', false) && !DB::table('seo_redirects')->where('old_url','/ogren/trafik-isaretleri')->exists()) {
                DB::table('seo_redirects')->insert([
                    'old_url'=>'/ogren/trafik-isaretleri','new_url'=>'/trafik-isaretleri','type'=>'301','status'=>'Aktif',
                    'sources'=>json_encode([['url'=>'/ogren/trafik-isaretleri','match_type'=>'exact','ignore_case'=>false]]),
                    'usage_count'=>0,'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        });
    }
    public function down(): void { /* Preserve subsequent administrator edits; restore from backup if needed. */ }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration {
    public function up(): void {
        foreach (DB::table('seo_not_found_logs')->select('url')->groupBy('url')->havingRaw('count(*) > 1')->pluck('url') as $url) {
            $rows = DB::table('seo_not_found_logs')->where('url',$url)->orderBy('id')->get();
            DB::table('seo_not_found_logs')->where('id',$rows->first()->id)->update([
                'request_count'=>$rows->sum('request_count'), 'first_seen_at'=>$rows->min('first_seen_at'), 'last_seen_at'=>$rows->max('last_seen_at')]);
            DB::table('seo_not_found_logs')->whereIn('id',$rows->skip(1)->pluck('id'))->delete();
        }
        Schema::table('seo_not_found_logs', fn (Blueprint $t) => $t->unique('url'));
        Schema::table('seo_redirects', function (Blueprint $t) {
            $t->string('source_model')->nullable(); $t->unsignedBigInteger('source_id')->nullable();
        });
    }
    public function down(): void {
        Schema::table('seo_not_found_logs', fn (Blueprint $t) => $t->dropUnique(['url']));
        Schema::table('seo_redirects', fn (Blueprint $t) => $t->dropColumn(['source_model','source_id']));
    }
};

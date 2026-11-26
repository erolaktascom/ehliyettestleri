<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void
    {
        DB::table('seo_route_settings')->where('route_key','like','blog.author:%')->orWhere('route_key','members.show')->delete();
    }
    public function down(): void
    {
        throw new RuntimeException('Individual profile SEO settings can only be restored from the pre-migration backup.');
    }
};
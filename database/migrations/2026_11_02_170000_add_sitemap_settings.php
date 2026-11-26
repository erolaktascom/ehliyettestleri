<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seo_settings', function (Blueprint $table) {
            $table->json('sitemap_sections')->nullable();
            $table->unsignedInteger('sitemap_links_per_file')->default(200);
        });
        $title = trim((string) DB::table('seo_settings')->value('site_title'));
        // Preserve an existing explicit homepage title; move the retired default only when empty.
        if ($title !== '') {
            $home = DB::table('seo_route_settings')->where('route_key', 'home')->first();
            if (!$home) DB::table('seo_route_settings')->insert(['route_key' => 'home', 'seo_title' => $title, 'seo_index' => 'inherit', 'created_at' => now(), 'updated_at' => now()]);
            elseif (trim((string) $home->seo_title) === '') DB::table('seo_route_settings')->where('id', $home->id)->update(['seo_title' => $title]);
        }
    }
    public function down(): void
    {
        Schema::table('seo_settings', fn (Blueprint $table) => $table->dropColumn(['sitemap_sections', 'sitemap_links_per_file']));
    }
};

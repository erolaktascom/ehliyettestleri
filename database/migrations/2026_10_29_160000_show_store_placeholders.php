<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $items = DB::table('navigation_items')->where('menu', 'footer')->where('footer_group', 'apps')
            ->where('url', '')->whereIn('name', ['Google Play', 'Play Store', 'App Store']);
        $items->update(['is_active' => true]);
        DB::table('navigation_items')->where('menu', 'footer')->where('footer_group', 'apps')
            ->where('name', 'Google Play')->where('url', '')->update(['name' => 'Play Store']);
    }

    public function down(): void {}
};

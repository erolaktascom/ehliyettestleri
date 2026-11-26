<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('navigation_items')->where('menu', 'footer')->where('footer_group', 'apps')
            ->where('name', 'App Store')->update(['order' => 1]);
        DB::table('navigation_items')->where('menu', 'footer')->where('footer_group', 'apps')
            ->whereIn('name', ['Play Store', 'Google Play'])->update(['order' => 2]);
    }

    public function down(): void {}
};

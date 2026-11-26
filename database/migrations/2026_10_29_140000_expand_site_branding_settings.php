<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('site_description')->nullable();
            $table->string('copyright_text', 500)->nullable();
            $table->json('social_links')->nullable();
        });
        DB::table('site_settings')->update([
            'site_description' => "Türkiye'nin ilk ve lider 3D sinematik MEB e-Sınav simülasyon ve sürücü belgesi hazırlık platformu.",
            'copyright_text' => '© {year} {site_name}. Tüm hakları saklıdır.',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['site_description', 'copyright_text', 'social_links']));
    }
};

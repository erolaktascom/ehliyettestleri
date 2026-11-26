<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seo_settings', fn (Blueprint $table) => $table->json('verification_tags')->nullable());
    }

    public function down(): void
    {
        Schema::table('seo_settings', fn (Blueprint $table) => $table->dropColumn('verification_tags'));
    }
};
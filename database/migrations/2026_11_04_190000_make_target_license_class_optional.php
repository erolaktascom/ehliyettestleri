<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('target_license_class')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('target_license_class')->update(['target_license_class' => '']);
        Schema::table('users', function (Blueprint $table) {
            $table->string('target_license_class')->nullable(false)->default('B Sınıfı Ehliyet')->change();
        });
    }
};
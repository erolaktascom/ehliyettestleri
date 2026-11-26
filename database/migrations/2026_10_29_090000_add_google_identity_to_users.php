<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Hash};
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique();
            $table->string('password')->nullable()->change();
        });
    }
    public function down(): void
    {
        foreach (DB::table('users')->whereNull('password')->pluck('id') as $id) {
            DB::table('users')->where('id', $id)->update(['password' => Hash::make(Str::random(64))]);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropUnique(['google_id']));
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
            $table->string('password')->nullable(false)->change();
        });
    }
};

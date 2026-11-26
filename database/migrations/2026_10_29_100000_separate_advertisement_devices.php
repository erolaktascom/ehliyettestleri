<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration {
    public function up(): void {
        Schema::table('advertisements', fn (Blueprint $table) => $table->string('device')->default('desktop')->index());
        DB::transaction(function () {
            foreach (DB::table('advertisements')->orderBy('id')->get() as $ad) {
                if (strtolower($ad->location) === 'header') {
                    DB::table('advertisements')->where('id', $ad->id)->update(['location' => 'Header']);
                    $copy = (array) $ad; unset($copy['id']);
                    $copy['device'] = 'mobile'; $copy['location'] = 'Header';
                    DB::table('advertisements')->insert($copy);
                } elseif (strtolower($ad->location) === 'sidebar') {
                    DB::table('advertisements')->where('id', $ad->id)->update(['location' => 'RightBanner']);
                }
            }
        });
    }
    public function down(): void {
        Schema::table('advertisements', fn (Blueprint $table) => $table->dropIndex(['device']));
        Schema::table('advertisements', fn (Blueprint $table) => $table->dropColumn('device'));
    }
};

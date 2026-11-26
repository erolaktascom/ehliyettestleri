<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void {
        Schema::table('advertisements', fn (Blueprint $table) => $table->string('audience')->default('["everyone"]')->change());
        DB::transaction(function () {
            foreach (DB::table('advertisements')->get(['id', 'audience']) as $ad) {
                $values = json_decode($ad->audience, true);
                if (is_array($values)) continue;
                $values = match ($ad->audience) {
                    'everyone' => ['everyone'], 'members' => ['members', 'premium'], 'guests' => ['guests'],
                    default => throw new RuntimeException('Bilinmeyen reklam hedef kitlesi: '.$ad->id),
                };
                DB::table('advertisements')->where('id', $ad->id)->update(['audience' => json_encode($values)]);
            }
        });
    }
    public function down(): void {
        throw new RuntimeException('Çoklu reklam kitleleri tek seçime kayıpsız dönüştürülemez. Geri dönüş için veritabanı yedeğini kullanın.');
    }
};

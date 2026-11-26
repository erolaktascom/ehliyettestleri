<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('traffic_signs', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->index();
        });
        Schema::create('user_sign_learning_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traffic_sign_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traffic_sign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filter', 20)->default('all');
            $table->timestamps();
            $table->unique(['user_id', 'traffic_sign_category_id'], 'sign_learning_user_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sign_learning_progress');
        Schema::table('traffic_signs', fn (Blueprint $table) => $table->dropColumn('is_published'));
    }
};

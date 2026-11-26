<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Remove only the original seed example, preserving administrator-entered numbers.
        DB::table('site_settings')->where('phone', '+90 850 123 45 67')->update(['phone' => null]);
    }

    public function down(): void {}
};

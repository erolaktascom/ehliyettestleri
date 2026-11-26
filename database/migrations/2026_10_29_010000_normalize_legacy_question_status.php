<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Older imports used "active" for published questions. Preserve dates and ownership.
        DB::table('questions')->where('status', 'active')->update(['status' => 'Yayında']);
    }

    public function down(): void
    {
        // Canonical values must stay valid; original legacy rows cannot be distinguished afterwards.
    }
};

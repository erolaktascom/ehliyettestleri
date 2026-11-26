<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Correct legacy labels without recording a content edit or changing dates.
        foreach (['kolay' => 'Kolay', 'orta' => 'Orta', 'zor' => 'Zor'] as $legacy => $label) {
            DB::table('questions')->whereRaw('LOWER(TRIM(difficulty)) = ?', [$legacy])
                ->where('difficulty', '!=', $label)->update(['difficulty' => $label]);
        }
    }

    public function down(): void
    {
        // Keep valid labels; reintroducing the legacy values would block editing.
    }
};

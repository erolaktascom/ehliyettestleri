<?php
namespace App\Models\Concerns;
use App\Support\AutomaticSlugRedirects;
use Illuminate\Support\Facades\DB;
trait TracksSlugRedirects {
    public function save(array $options = []) {
        return DB::transaction(fn () => parent::save($options));
    }
    public static function bootTracksSlugRedirects(): void {
        static::updated(fn ($record) => AutomaticSlugRedirects::changed($record));
        static::created(fn ($record) => AutomaticSlugRedirects::assertAvailable($record));
    }
}

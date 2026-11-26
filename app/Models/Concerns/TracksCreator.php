<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksCreator
{
    public static function bootTracksCreator(): void
    {
        static::creating(function (Model $record) {
            $user = auth()->user();
            $isStaff = $user instanceof User && ($user->isAdministrator() || $user->isEditor());
            $record->created_by = $isStaff ? $user->getKey() : null;
            $record->creator_name = $isStaff ? $user->name : null;
        });

        static::updating(function (Model $record) {
            // Editing a record must never replace its original creator, including unknown legacy creators.
            $record->created_by = $record->getOriginal('created_by');
            $record->creator_name = $record->getOriginal('creator_name');
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

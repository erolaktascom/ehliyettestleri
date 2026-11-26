<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationAudience
{
    public const GROUPS = ['everyone' => 'Herkes', 'members' => 'Üyeler', 'premium' => 'Premium Üyeler'];

    public static function legacyGroups(string $target): array
    {
        return match ($target) {
            'Tüm Üyeler', 'Herkes', 'all', 'everyone' => ['everyone'],
            'Premium Üyeler' => ['premium'],
            default => [],
        };
    }

    public static function query(array $groups): Builder
    {
        $query = User::query();
        if (in_array('everyone', $groups, true)) return $query;

        return $query->where(function (Builder $query) use ($groups) {
            $query->whereRaw('1 = 0');
            $now = now();
            if (in_array('members', $groups, true)) {
                $query->orWhere(fn (Builder $q) => $q->whereNull('premium_until')->orWhere('premium_until', '<=', $now));
            }
            if (in_array('premium', $groups, true)) {
                $query->orWhere('premium_until', '>', $now);
            }
        });
    }

    public static function label(array $groups): string
    {
        return implode(', ', array_map(fn ($group) => self::GROUPS[$group], $groups));
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class TrackMemberActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $user = $request->user();
        if ($user && !$user->is_admin && (!$user->last_active_at || $user->last_active_at->lt(now()->subMinutes(5)))) {
            // Activity is not a profile edit and must not alter updated_at or audit history.
            User::whereKey($user->id)->toBase()->update(['last_active_at' => now()]);
        }
        return $response;
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuthorProfiles;

class MemberProfileController extends Controller
{
    public function __invoke(string $slug)
    {
        $user = User::where('profile_slug', $slug)->where('is_admin', false)
            ->firstOrFail(['id', 'name', 'profile_slug', 'biography', 'avatar_path', 'created_at']);
        $publicProfile = AuthorProfiles::data($user);
        return view('pages.profiles.member', compact('publicProfile'));
    }
}
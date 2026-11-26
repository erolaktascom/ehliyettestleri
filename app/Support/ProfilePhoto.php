<?php
namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePhoto
{
    public const RULES = 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096';

    public static function save(User $user, ?UploadedFile $photo, bool $remove = false): void
    {
        if (!$photo && !$remove) return;
        $old = $user->avatar_path;
        $path = $photo?->store('profile-images', 'public');
        try {
            $user->avatar_path = $path;
            $user->save();
        } catch (\Throwable $e) {
            if ($path) Storage::disk('public')->delete($path);
            throw $e;
        }
        if ($old && str_starts_with($old, 'profile-images/')) app(MediaLibrary::class)->cleanup($old);
    }
}
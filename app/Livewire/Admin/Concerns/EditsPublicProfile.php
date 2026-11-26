<?php
namespace App\Livewire\Admin\Concerns;

use App\Models\User;
use App\Support\ProfilePhoto;
use Livewire\WithFileUploads;

trait EditsPublicProfile
{
    use WithFileUploads;
    public string $biography = '';
    public $profile_photo;
    public bool $remove_photo = false;

    protected function publicProfileRules(): array
    {
        return ['biography' => 'nullable|string|max:1500', 'profile_photo' => ProfilePhoto::RULES, 'remove_photo' => 'boolean'];
    }
    protected function savePublicProfile(User $user): void
    {
        $user->update(['biography' => $this->biography]);
        ProfilePhoto::save($user, $this->profile_photo, $this->remove_photo);
        $this->reset('profile_photo', 'remove_photo');
    }
}
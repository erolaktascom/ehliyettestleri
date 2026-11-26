@props(['userId' => null])
@php($profileUser = $userId ? \App\Models\User::find($userId) : null)
<fieldset class="rounded-xl border border-slate-200 p-5 space-y-4">
    <legend class="px-2 text-sm font-bold text-slate-800">Herkese Açık Profil</legend>
    @if($profileUser?->profile_slug)
        <a href="{{ route($profileUser->is_admin ? 'blog.author' : 'members.show', $profileUser->profile_slug) }}" target="_blank" rel="noopener" class="text-sm text-blue-600 font-semibold">Profili Görüntüle ↗</a>
    @endif
    <div>
        <label for="profile-photo" class="block text-xs font-bold text-slate-700 mb-2">Profil Fotoğrafı</label>
        @if($profileUser?->avatar_url)
            <img src="{{ $profileUser->avatar_url }}" alt="Mevcut profil fotoğrafı" class="size-16 rounded-xl object-cover mb-3">
            <label class="flex items-center gap-2 text-xs mb-3"><input type="checkbox" wire:model="remove_photo"> Fotoğrafı kaldır</label>
        @endif
        <input id="profile-photo" type="file" wire:model="profile_photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
        <p class="text-xs text-slate-500 mt-2">JPG, PNG veya WebP · En fazla 2 MB</p>
        <p wire:loading wire:target="profile_photo" class="text-xs text-blue-600">Fotoğraf yükleniyor…</p>
        @error('profile_photo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="profile-bio" class="block text-xs font-bold text-slate-700 mb-2">Biyografi</label>
        <textarea id="profile-bio" wire:model="biography" rows="4" maxlength="1500" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
        @error('biography') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
</fieldset>
<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.staff-users') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $userId ? 'Yöneticiyi Düzenle' : 'Yeni Yönetici Ekle' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">Admin paneline erişimi olan personel ve moderatör hesapları.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ad Soyad *</label>
                <input type="text" wire:model="name" placeholder="Ad Soyad" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">E-posta Adresi *</label>
                <input type="email" wire:model="email" placeholder="yonetici@example.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Rol Tanımı *</label>
                <select wire:model="role_name" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"><option value="Yönetici">Yönetici</option><option value="Editör">Editör</option></select>
                @error('role_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">{{ $userId ? 'Yeni Şifre (Boş bırakılırsa değişmez)' : 'Şifre *' }}</label>
                <input type="password" wire:model="password" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <x-admin.public-profile-fields :user-id="$userId" />
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                {{ $userId ? 'Değişiklikleri Kaydet' : 'Yöneticiyi Oluştur' }}
            </button>
        </div>
    </form>
</div>

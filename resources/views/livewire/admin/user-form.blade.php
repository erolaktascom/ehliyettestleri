<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $userId ? 'Kullanıcıyı Düzenle' : 'Yeni Kullanıcı Ekle' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">Öğrenci veya yönetici hesabı bilgilerini yönetin.</p>
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
                <input type="email" wire:model="email" placeholder="ornek@email.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Şifre {{ $userId ? '(Değiştirmeyecekseniz boş bırakın)' : '*' }}</label>
                <input type="password" wire:model="password" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('password') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Telefon Numarası</label>
                <input type="text" wire:model="phone" placeholder="0555 123 4567" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('phone') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Hedef Ehliyet Sınıfı</label>
            <input type="text" wire:model="target_license_class" placeholder="B" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('target_license_class') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="pt-2">
            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer hover:bg-slate-50">
                <input type="checkbox" wire:model.live="is_admin" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <div>
                    <span class="text-sm font-semibold text-slate-800 block">Panel Erişimi Ver</span>
                    <span class="text-xs text-slate-500">Bu kullanıcı yönetim paneline erişebilir ve içerikleri yönetebilir.</span>
                </div>
            </label>
        </div>

        @error('is_admin') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        @if($is_admin)
            <label class="block text-sm font-semibold">Panel Rolü
                <select wire:model="role_name" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5">
                    <option value="Yönetici">Yönetici</option>
                    <option value="Editör">Editör</option>
                </select>
            </label>
            @error('role_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        @endif
        <x-admin.public-profile-fields :user-id="$userId" />
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                {{ $userId ? 'Değişiklikleri Kaydet' : 'Kullanıcıyı Oluştur' }}
            </button>
        </div>
    </form>
</div>

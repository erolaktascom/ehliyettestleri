<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.navigation-items.'.$menu) }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $itemId ? 'Menü Öğesini Düzenle' : 'Yeni Menü Öğesi Ekle' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ \App\Models\NavigationItem::ADMIN_MENUS[$menu] }} · Bu değişiklik yalnızca seçili menüyü etkiler.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        @if($menu === 'footer')
            <div>
                <label for="footer-group" class="block text-xs font-bold text-slate-700 uppercase mb-1">Alt Bilgi Konumu</label>
                <select id="footer-group" wire:model.live="footer_group" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
                    <option value="">Sütun seçin</option>
                    @foreach($footerGroups as $group)<option value="{{ $group->key }}">{{ $group->name }}</option>@endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Bağlantının görüneceği sütunu seçin. Sıralama seçilen konum içinde uygulanır.</p>
                @error('footer_group') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        @endif
        @if(in_array($menu, ['footer', 'apps']) && $footerGroups->isEmpty())
            <p class="text-sm text-amber-700">Önce bir sütun oluşturun. <a class="underline font-semibold" href="{{ route('admin.navigation-items.'.$menu.'.columns.create') }}">Sütun Ekle</a></p>
        @endif
        @if($menu === 'apps') @error('footer_group')<p class="text-sm text-red-600">Uygulamalar sütunu bulunamadı. Önce sütunu oluşturun.</p>@enderror @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Menü Adı *</label>
                <input type="text" wire:model="name" placeholder="Örn: Deneme Sınavları" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Bağlantı (URL) {{ $menu === 'apps' ? '' : '*' }}</label>
                @if($menu === 'apps')<p class="text-xs text-slate-500 mb-2">Boş bırakırsanız mağaza adı bağlantısız görünür. Adres eklediğinizde tıklanabilir olur.</p>@endif
                <input type="text" wire:model="url" placeholder="/deneme-sinavlari veya https://..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('url') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Hedef Kitle *</label>
                <select wire:model="audience" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(\App\Models\NavigationItem::AUDIENCES as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach
                </select>
                @error('audience') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sıralama *</label>
                <input type="number" wire:model="order" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('order') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="pt-2">
            <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer hover:bg-slate-50">
                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600 size-4">
                <span class="text-xs font-semibold text-slate-700">Aktif Durumda</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                {{ $itemId ? 'Değişiklikleri Kaydet' : 'Menü Öğesini Oluştur' }}
            </button>
        </div>
    </form>
</div>

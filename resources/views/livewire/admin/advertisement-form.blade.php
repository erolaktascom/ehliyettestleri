<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.advertisements.'.$device) }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $adId ? 'Reklam Alanını Düzenle' : 'Yeni Reklam Alanı Ekle' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ \App\Models\Advertisement::DEVICES[$device] }} · Değişiklik yalnızca bu cihazın reklamını etkiler.</p>
            </div>
        </div>
    </div>

    <p class="text-sm text-slate-500">Önerilen ölçüler: {{ $device === 'mobile' ? 'Header 320 × 100 px.' : 'Header 728 × 90 px; sağ/sol banner 160 × 600 px.' }} Reklam içeriğini HTML / JavaScript kodu olarak ekleyin.</p>
    <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Reklam Başlığı / Tanımı *</label>
            <input type="text" wire:model="title" placeholder="Örn: Sınav Sayfası Üst Banner" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('title') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Yerleşim Konumu *</label>
                <select wire:model="location" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(\App\Models\Advertisement::locations($device) as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('location') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">HTML / JavaScript Reklam Kodu</label>
            <textarea wire:model="code" rows="5" placeholder="<script async src='https://pagead2.googlesyndication.com...'></script>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            <p class="text-xs text-slate-500 mt-1">Tıklama bağlantısını reklam koduna ekleyin. Bağlantının yeni sekmede açılması için target="_blank" kullanabilirsiniz.</p>
            @error('code') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <fieldset>
            <legend class="block text-xs font-bold text-slate-700 uppercase mb-3">Kimlere Gösterilsin?</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach(\App\Models\Advertisement::SELECTABLE_AUDIENCES as $key => $label)
                    <label wire:key="ad-audience-{{ $key }}-{{ in_array($key, $audience, true) ? 'selected' : 'unselected' }}" class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer {{ in_array($key, $audience, true) ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white' }}">
                        <input type="checkbox" wire:click="toggleAudience('{{ $key }}')" @checked(in_array($key, $audience, true)) class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-slate-500 mt-3">Birden fazla grup seçebilirsiniz. Herkes, ziyaretçileri ve standart üyeleri kapsar. Premium süresi devam eden kullanıcılara hiçbir reklam gösterilmez.</p>
            @error('audience')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            @error('audience.*')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </fieldset>
        <div class="pt-2">
            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer hover:bg-slate-50">
                <input type="checkbox" wire:model="is_active" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <div>
                    <span class="text-sm font-semibold text-slate-800 block">Reklam Yayında</span>
                    <span class="text-xs text-slate-500">Reklam yalnızca seçilen cihazda ve seçilen kitleye gösterilir.</span>
                </div>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                {{ $adId ? 'Değişiklikleri Kaydet' : 'Reklam Alanını Oluştur' }}
            </button>
        </div>
    </form>
</div>

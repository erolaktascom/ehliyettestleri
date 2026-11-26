<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div>
        <h1 class="text-2xl font-bold text-slate-900">SEO Ayarları</h1>
        <p class="text-sm text-slate-500 mt-1">Sabit sayfaların arama motoru bilgilerini yönetin. Yazar ve üye profilleri Genel Ayarlar bölümündeki ayrı ortak kurallardan yönetilir.</p>
    </div>
    <x-admin.seo-tabs active="seo-settings.pages" />
    <form wire:submit="save" class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <label for="seo-page" class="block text-xs font-bold text-slate-700 uppercase mb-2">Sayfa</label>
            <select id="seo-page" wire:model.live="selectedPage" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                @foreach($choices as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
            @error('selectedPage') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            @if($pageUrl)<a href="{{ $pageUrl }}" target="_blank" rel="noopener" class="block mt-3 text-sm text-blue-600 break-all">{{ $pageUrl }} ↗</a>@endif
        </div>
        <div wire:key="seo-page-fields-{{ $selectedPage }}"><x-admin.seo-fields :general="false" /></div>
        <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-xl text-white text-sm font-semibold disabled:opacity-50">SEO Ayarlarını Kaydet</button></div>
    </form>
</div>
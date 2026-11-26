@props(['general' => true, 'custom' => false])
<section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-4 mt-6" aria-labelledby="content-seo-heading">
    <div>
        <h2 id="content-seo-heading" class="text-lg font-bold text-slate-900">SEO Ayarları</h2>
        <p class="text-xs text-slate-500 mt-1">{{ $general ? 'Özel başlık ve açıklama önceliklidir. Boş alanlarda genel SEO kuralları, kural da boşsa otomatik içerik bilgileri kullanılır.' : 'Bu sayfanın SEO bilgilerini buradan yönetin. Boş başlık ve açıklama alanlarında sayfanın kendi bilgileri kullanılır.' }}</p>
    </div>
    <div>
        <p class="text-xs text-slate-500 mb-3">Başlık ve açıklamada %site_adi% kullanırsanız Site Ayarlarındaki ad otomatik gösterilir.</p>
        <label for="seo-title" class="block text-xs font-bold text-slate-700 uppercase mb-2">SEO Başlığı</label>
        <input id="seo-title" wire:model.live.debounce.300ms="seo_title" maxlength="255" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        @error('seo_title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="seo-description" class="block text-xs font-bold text-slate-700 uppercase mb-2">SEO Açıklaması</label>
        <textarea id="seo-description" wire:model.live.debounce.300ms="seo_description" rows="3" maxlength="1000" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        @error('seo_description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="seo-canonical" class="block text-xs font-bold text-slate-700 uppercase mb-2">Canonical Adresi</label>
        <input id="seo-canonical" type="url" wire:model="seo_canonical" placeholder="Boş bırakırsanız bu sayfanın kendi adresi kullanılır" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <p class="text-xs text-slate-500 mt-1">Yalnız farklı bir canonical adresi kullanmak istiyorsanız doldurun. Boşken sayfanın güncel adresi otomatik kullanılır.</p>
        @error('seo_canonical') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
    <fieldset>
        <legend class="block text-xs font-bold text-slate-700 uppercase mb-2">Arama Motoru Görünürlüğü</legend>
        <div @class(['grid grid-cols-1 gap-3', 'sm:grid-cols-3' => $general && !$custom, 'sm:grid-cols-2' => !$general || $custom])>
            @if($general && !$custom)
            <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                <input type="radio" name="seo_index" value="inherit" wire:model="seo_index" class="size-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                <span><span class="block text-sm font-semibold text-slate-800">Genel ayarı kullan</span><span class="block text-xs text-slate-500 mt-0.5">İçerik türünün kuralını izle</span></span>
            </label>
            @endif
            <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                <input type="radio" name="seo_index" value="index" wire:model="seo_index" class="size-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                <span><span class="block text-sm font-semibold text-slate-800">Index</span><span class="block text-xs text-slate-500 mt-0.5">Arama motorlarında göster</span></span>
            </label>
            <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                <input type="radio" name="seo_index" value="noindex" wire:model="seo_index" class="size-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                <span><span class="block text-sm font-semibold text-slate-800">Noindex</span><span class="block text-xs text-slate-500 mt-0.5">Arama motorlarında gösterme</span></span>
            </label>
        </div>
        <p class="text-xs text-slate-500 mt-2">Noindex sayfayı ziyaretçilere kapatmaz; arama motorlarına dizine eklememelerini bildirir.</p>
        @error('seo_index') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        @if($general && $custom)
            <p class="text-xs text-slate-500">Özel SEO başlığı veya açıklaması girildiğinde bu sayfanın kendi Index / Noindex seçimi kullanılır.</p>
        @endif
    </fieldset>
</section>

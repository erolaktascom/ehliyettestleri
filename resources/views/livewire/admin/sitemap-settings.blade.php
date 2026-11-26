<div class="space-y-6 max-w-5xl">
    <x-admin.flash-messages />
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Sitemap Ayarları</h1>
            <p class="text-sm text-slate-500 mt-1">Site haritasına dahil edilecek bölümleri seçin. Her bölüm ayrı bir XML dosyası olarak oluşturulur.</p>
        </div>
        <button type="submit" form="sitemap-settings-form" wire:loading.attr="disabled" wire:target="save" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold disabled:opacity-50">Değişiklikleri Kaydet</button>
    </div>
    <x-admin.seo-tabs active="sitemap-settings" />
    <form id="sitemap-settings-form" wire:submit="save" class="space-y-6">
        <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">XML Site Haritası</h2>
                    <p class="text-xs text-slate-500 mt-1">Kaydettiğiniz seçimler otomatik uygulanır; ayrıca dosya oluşturmanız gerekmez.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 whitespace-nowrap">
                    <input type="checkbox" wire:model="sitemap_enabled" class="size-4 rounded text-blue-600 border-slate-300"> Aktif
                </label>
            </div>
            <div class="rounded-xl bg-blue-50 border border-blue-100 p-4 break-all">
                <span class="block text-xs font-bold text-blue-900 mb-1">Ana Sitemap İndeksi</span>
                @if($savedEnabled)
                    <a href="{{ route('sitemap.index') }}" target="_blank" rel="noopener" class="text-sm text-blue-700 underline">{{ route('sitemap.index') }} ↗</a>
                @else
                    <span class="text-sm text-slate-600">Site haritası kapalı. Etkinleştirip kaydedebilirsiniz.</span>
                @endif
                <p class="text-xs text-slate-600 mt-2">Arama motoruna yalnız ana indeksi göndermeniz yeterli. Alt sitemap’ler bu indekste listelenir.</p>
            </div>
            <div class="sm:max-w-sm">
                <label for="sitemap-limit" class="block text-xs font-bold text-slate-700 mb-2">Dosya Başına Bağlantı Sayısı</label>
                <input id="sitemap-limit" type="number" min="1" max="1000" wire:model="sitemap_links_per_file" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm">
                <p class="text-xs text-slate-500 mt-1">Sınırı aşan bölümler otomatik 2, 3… diye bölünür. Varsayılan: 200.</p>
                @error('sitemap_links_per_file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </section>
        <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Dahil Edilecek Bölümler</h2>
                <p class="text-xs text-slate-500 mt-1">İşareti kaldırılan bölüm ana indeksten çıkarılır ve alt sitemap’i kapatılır. Bu işlem sayfanın Index ayarını değiştirmez.</p>
            </div>
            @error('sitemap_sections') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @error('sitemap_sections.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @foreach(['İçerikler', 'Kategoriler', 'Diğer Sayfalar'] as $group)
                <fieldset class="space-y-3">
                    <legend class="text-xs font-bold text-slate-500 uppercase mb-3">{{ $group }}</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($sections as $key => $section)
                            @if($section['group'] === $group)
                                <div wire:key="sitemap-{{ $key }}" class="rounded-xl border border-slate-200 p-4 space-y-2 has-[:checked]:bg-blue-50/40 has-[:checked]:border-blue-200">
                                    <label class="flex gap-3 items-center cursor-pointer">
                                        <input type="checkbox" wire:model="sitemap_sections" value="{{ $key }}" class="size-4 rounded text-blue-600 border-slate-300">
                                        <span class="text-sm font-semibold text-slate-800 flex-1">{{ $section['label'] }}</span>
                                        <span class="text-xs text-slate-500 whitespace-nowrap">{{ $counts[$key] }} URL</span>
                                    </label>
                                    <div class="pl-7 text-xs break-all">
                                        @if($savedEnabled && in_array($key, $savedSections, true) && $counts[$key] > 0)
                                            <a href="{{ route('sitemap.section', ['section' => $key]) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ \App\Support\SiteSitemap::path($key) }} ↗</a>
                                        @else
                                            <span class="text-slate-400">{{ \App\Support\SiteSitemap::path($key) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <p class="text-xs text-slate-500 border-t border-slate-100 pt-4 leading-5">Sayılar yayındaki ve sitemap’e uygun URL’leri gösterir. Üyelik gerektiren denemeler, taslak, ileri tarihli blog, Noindex, yönlendirilmiş ve farklı canonical adresi olan sayfalar eklenmez. Boş bölümler ana indekste yer almaz. Seçim değişiklikleri Kaydet sonrası uygulanır.</p>
        </section>
        <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 space-y-3">
            <h2 class="text-lg font-bold text-slate-900">Robots.txt</h2>
            <label for="sitemap-robots" class="block text-xs text-slate-500">Mevcut tarama kuralları</label>
            <textarea id="sitemap-robots" wire:model="robots_txt" rows="5" class="w-full px-4 py-3 border border-slate-200 rounded-xl text-xs font-mono"></textarea>
            <p class="text-xs text-slate-500">Sitemap satırı aktifliğe göre otomatik eklenir veya kaldırılır; buraya ayrıca yazmanız gerekmez.</p>
            @error('robots_txt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <a href="{{ route('site.robots') }}" target="_blank" rel="noopener" class="inline-block text-xs text-blue-600 underline">Robots.txt dosyasını görüntüle ↗</a>
        </section>
    </form>
</div>
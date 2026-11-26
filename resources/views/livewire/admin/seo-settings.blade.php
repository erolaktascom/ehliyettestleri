<div class="mx-auto max-w-5xl space-y-5">
    <x-admin.flash-messages />

    <header class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-slate-500">SEO Ayarları</p>
            <h1 class="text-2xl font-bold text-slate-900">Genel Ayarlar</h1>
        </div>
        <button form="seo-general-form" type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Kaydet</button>
    </header>
    <x-admin.seo-tabs active="seo-settings" />

    <form id="seo-general-form" wire:submit="save" class="space-y-4">
        <div role="group" aria-label="Genel ayar bölümleri" class="flex gap-5 border-b border-slate-200">
            @foreach(['rules' => 'İçerik kuralları', 'pagination' => 'Sayfalama'] as $key => $sectionLabel)
                <button type="button" wire:click="$set('section', '{{ $key }}')" aria-pressed="{{ $section === $key ? 'true' : 'false' }}"
                    @class(['border-b-2 py-2.5 text-sm font-semibold', 'border-blue-600 text-blue-700' => $section === $key, 'border-transparent text-slate-600 hover:text-blue-700' => $section !== $key])>{{ $sectionLabel }}</button>
            @endforeach
        </div>

        @if($section === 'pagination')
            <section wire:key="pagination" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <fieldset class="space-y-3">
                    <legend class="text-lg font-bold text-slate-900">Genel Sayfalama</legend>
                    <p class="text-sm text-slate-600">Ziyaretçiye açık listelerde ikinci ve sonraki sayfaların görünürlüğünü seçin.</p>
                    @foreach(['index' => 'Index', 'noindex' => 'Noindex'] as $value => $label)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm font-medium cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="pagination-index" wire:model.live="pagination_index" value="{{ $value }}"> {{ $label }}
                        </label>
                    @endforeach
                    @error('pagination_index') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>
                <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm">
                    <h3 class="font-semibold text-slate-700">Örnek · Blog listesi Index iken</h3>
                    <dl class="mt-2 divide-y divide-slate-200" aria-live="polite">
                        <div class="flex items-center justify-between gap-2 py-2"><dt><code>/blog</code></dt><dd>Index</dd></div>
                        <div class="flex items-center justify-between gap-2 py-2"><dt><code>{{ route('blog.index.page', ['page' => 2], false) }}</code></dt><dd>{{ $pagination_index === 'noindex' ? 'Noindex' : 'Index' }}</dd></div>
                    </dl>
                </div>
            </section>
        @else
            <div wire:key="rules" class="grid items-start gap-4 lg:grid-cols-[14rem_minmax(0,1fr)]">
                <aside class="hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:block">
                    <h2 class="px-3 py-2 text-sm font-semibold text-slate-700">İçerik türü</h2>
                    @foreach($seoTypes as $key => $label)
                        <button type="button" wire:click="$set('selectedType', '{{ $key }}')" aria-pressed="{{ $type === $key ? 'true' : 'false' }}"
                            @class(['block w-full rounded-lg px-3 py-2 text-left text-sm font-medium', 'bg-blue-50 text-blue-700' => $type === $key, 'text-slate-600 hover:bg-slate-50' => $type !== $key])>{{ $label }}</button>
                    @endforeach
                </aside>
                <section class="min-w-0 space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <label class="block text-sm font-semibold text-slate-700 lg:hidden">İçerik türü
                        <select wire:model.live="selectedType" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                            @foreach($seoTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <div wire:key="editor-{{ $type }}" class="space-y-4">
                        <h2 class="hidden text-lg font-bold text-slate-900 lg:block">{{ $seoTypes[$type] }}</h2>
                        <label class="block text-sm font-semibold text-slate-700">SEO başlık şablonu
                            <input wire:model.live.debounce.300ms="content_rules.{{ $type }}.title" maxlength="255" placeholder="%baslik% | %site_adi%" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 font-normal">
                        </label>
                        @error('content_rules.'.$type.'.title') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <label class="block text-sm font-semibold text-slate-700">Meta açıklama şablonu
                            <textarea wire:model.live.debounce.300ms="content_rules.{{ $type }}.description" maxlength="1000" rows="2" placeholder="%ozet%" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 font-normal"></textarea>
                        </label>
                        @error('content_rules.'.$type.'.description') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <details class="text-xs text-slate-600">
                            <summary class="cursor-pointer font-semibold">Kullanılabilir değişkenler</summary>
                            <p class="mt-2 leading-6"><code>%baslik%</code> içerik başlığı · <code>%site_adi%</code> site adı · <code>%ozet%</code> içerik özeti · <code>%kategori%</code> kategori · <code>%yil%</code> yıl · <code>%ad%</code> profil adı · <code>%kullanici_adi%</code> profil adresindeki ad.</p>
                        </details>
                        <label class="block text-sm font-semibold text-slate-700">Arama motoru görünürlüğü
                            <select wire:model="content_rules.{{ $type }}.index" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-normal">
                                <option value="index">Index</option><option value="noindex">Noindex</option>
                            </select>
                        </label>
                        <p class="text-xs text-slate-600">{{ $type === 'members' ? 'Bütün üye profillerine uygulanır.' : '“Genel ayarı kullan” seçili içeriklere uygulanır.' }}</p>
                        @error('content_rules.'.$type.'.index') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('content_rules') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <div class="space-y-1 rounded-xl bg-slate-50 p-4 break-words">
                            <h3 class="text-xs font-semibold text-slate-600">Arama sonucu önizlemesi</h3>
                            @if($preview['sample'])
                                <p class="text-xs text-slate-600">{{ $preview['sample'] }}</p>
                                <p class="font-semibold text-blue-700">{{ $preview['title'] ?: 'İçeriğin kendi başlığı kullanılır.' }}</p>
                                <p class="text-sm text-slate-600">{{ $preview['description'] ?: 'İçeriğin kendi açıklaması kullanılır.' }}</p>
                            @else
                                <p class="text-sm text-slate-600">Önizleme için bu bölümde henüz içerik yok.</p>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        @endif
    </form>
</div>

<div class="max-w-5xl space-y-6">
    <x-admin.flash-messages />
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.seo-redirects') }}" aria-label="Yönlendirmelere dön" class="p-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $redirectId ? 'Yönlendirmeyi Düzenle' : 'Yeni Yönlendirme' }}</h1>
    </div>
    <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8 space-y-10">
            <div class="grid md:grid-cols-[160px_1fr] gap-4">
                <div class="text-sm font-semibold text-slate-800 md:pt-3">Kaynak Adresler</div>
                <div class="space-y-4 min-w-0">
                    @foreach($sources as $index => $source)
                        <div wire:key="source-row-{{ $index }}" class="space-y-2">
                            <div class="flex flex-wrap sm:flex-nowrap items-start gap-2">
                                <div class="flex-1 min-w-0 w-full">
                                    <label class="sr-only" for="source-{{ $index }}">Kaynak adres {{ $index + 1 }}</label>
                                    <input id="source-{{ $index }}" type="text" wire:model="sources.{{ $index }}.url" placeholder="/eski-adres" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <label class="sr-only" for="match-{{ $index }}">Eşleşme türü {{ $index + 1 }}</label>
                                <select id="match-{{ $index }}" wire:model.live="sources.{{ $index }}.match_type" class="w-44 px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    @foreach(\App\Support\SeoRedirectRules::MATCH_TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if(count($sources) > 1)
                                    <button type="button" wire:click="removeSource({{ $index }})" class="px-2 py-2.5 text-sm text-red-600 hover:text-red-800" aria-label="Kaynak {{ $index + 1 }} kaldır">Kaldır</button>
                                @endif
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                                <input type="checkbox" wire:model="sources.{{ $index }}.ignore_case" class="rounded border-slate-300 text-blue-600">
                                Büyük/küçük harfi yok say
                            </label>
                            @error('sources.'.$index.'.url') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('sources.'.$index.'.match_type') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('sources.'.$index.'.ignore_case') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    @error('sources') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="button" wire:click="addSource" @disabled(count($sources) >= 20) class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40">+ Başka Kaynak Ekle</button>
                    <p class="text-xs leading-5 text-slate-400">Birden fazla adresi aynı hedefe bağlayabilirsiniz. İçerir, başlangıç, bitiş ve düzenli ifade kuralları adresin yolunda aranır.</p>
                    @if(collect($sources)->contains('match_type', 'regex'))
                        <p class="text-xs text-slate-500">Düzenli ifade örneği: <code>^/eski/(.*)$</code> → <code>/yeni/$1</code>. Deseni ayraç eklemeden yazın.</p>
                    @endif
                </div>
            </div>
            <div class="grid md:grid-cols-[160px_1fr] gap-4">
                @if($type !== '410')
                    <label for="target-url" class="text-sm font-semibold text-slate-800 md:pt-3">Hedef Adres</label>
                    <div>
                        <input id="target-url" type="text" wire:model="new_url" placeholder="/yeni-adres veya https://ornek.com/sayfa" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        @error('new_url') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="text-sm font-semibold text-slate-800">Kaldırılan İçerik</div>
                    <p class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">Bu adresler 410 yanıtıyla kalıcı olarak kaldırılmış gösterilir. Hedef adres gerekmez.</p>
                @endif
            </div>
            <fieldset class="grid md:grid-cols-[160px_1fr] gap-4">
                <legend class="sr-only">İşlem Türü</legend>
                <div aria-hidden="true" class="text-sm font-semibold text-slate-800 md:pt-3">İşlem Türü</div>
                <div>
                    <div class="flex flex-wrap gap-2 rounded-lg border border-slate-200 p-1.5">
                        @foreach(['301' => '301 Kalıcı Yönlendirme', '302' => '302 Geçici Yönlendirme', '410' => '410 İçerik Kaldırıldı'] as $value => $label)
                            <label class="cursor-pointer flex-1 whitespace-nowrap">
                                <input type="radio" wire:model.live="type" value="{{ $value }}" class="peer sr-only">
                                <span class="block text-center px-3 py-2.5 rounded-md border border-slate-200 text-sm text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </fieldset>
            <fieldset class="grid md:grid-cols-[160px_1fr] gap-4">
                <legend class="sr-only">Durum</legend>
                <div aria-hidden="true" class="text-sm font-semibold text-slate-800 md:pt-3">Durum</div>
                <div>
                    <div class="inline-flex gap-1 rounded-lg border border-slate-200 p-1.5">
                        @foreach(['Aktif' => 'Etkin', 'Pasif' => 'Devre Dışı'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="status" value="{{ $value }}" class="peer sr-only">
                                <span class="block px-4 py-2 rounded-md border border-slate-200 text-sm text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('status') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </fieldset>
        </div>
        <div class="flex justify-end px-6 sm:px-8 py-5 bg-slate-50 border-t border-slate-200">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold disabled:opacity-50">{{ $redirectId ? 'Değişiklikleri Kaydet' : 'Yönlendirmeyi Ekle' }}</button>
        </div>
    </form>
</div>

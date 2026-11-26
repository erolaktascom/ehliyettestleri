<div class="space-y-6">
    <x-admin.flash-messages />
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Medya Yönetimi</h1>
            <p class="mt-1 text-sm text-slate-500">Yüklenen görselleri ve kullanıldıkları içerikleri inceleyin; kullanılmayanları silin.</p>
        </div>
        <button type="button" wire:click="$refresh" wire:loading.attr="disabled" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Yenile</button>
    </div>
    <div class="grid grid-cols-3 gap-3">
        @foreach(['total' => 'Tüm Görseller', 'used' => 'Kullanılan', 'unused' => 'Kullanılmayan'] as $key => $label)
            <div class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-medium text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $counts[$key] }}</p></div>
        @endforeach
    </div>
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search" aria-label="Görsel ara" placeholder="Dosya adı veya kullanıldığı içerikte ara…" class="min-w-0 flex-1 rounded-xl border-slate-200 text-sm">
        <select wire:model.live="filter" aria-label="Kullanım durumu" class="rounded-xl border-slate-200 text-sm"><option value="">Tüm Görseller</option><option value="used">Kullanılanlar</option><option value="unused">Kullanılmayanlar</option></select>
    </div>
    @error('media')<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>@enderror
    <p class="text-xs text-slate-500">Taslak içerikler de kontrol edilir. Yeni yüklenen görseller ilk 1 saat silinmeye karşı korunur. Henüz kaydedilmemiş açık formlardaki görseller kullanım listesine yansımaz.</p>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($media as $file)
            <article wire:key="media-{{ md5($file['path']) }}" class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <a href="{{ $file['url'] }}" target="_blank" rel="noopener" aria-label="Görseli aç: {{ $file['name'] }}" class="flex h-44 items-center justify-center border-b border-slate-100 bg-slate-50 p-4">
                    <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy" class="max-h-full max-w-full object-contain">
                </a>
                <div class="flex flex-1 flex-col gap-3 p-4">
                    <div><h2 class="break-all text-sm font-semibold text-slate-800">{{ $file['name'] }}</h2><p class="mt-1 break-all text-xs text-slate-500">{{ $file['path'] }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($file['bytes'] / 1024, 1, ',', '.') }} KB</p></div>
                    @if($file['uses'])
                        <details class="rounded-xl bg-emerald-50 p-3">
                            <summary class="cursor-pointer text-sm font-semibold text-emerald-700">{{ count($file['uses']) }} kayıtta kullanılıyor</summary>
                            <ul class="mt-3 space-y-3">
                                @foreach($file['uses'] as $use)
                                    <li class="text-xs"><span class="block text-slate-500">{{ $use['label'] }} · #{{ $use['id'] }}</span>
                                        @if(auth()->user()->canAccessAdminRoute($use['route']))
                                            <a href="{{ $use['url'] }}" class="mt-1 block break-words font-medium text-blue-700 hover:underline">{{ $use['title'] }}</a>
                                        @else <span class="mt-1 block break-words text-slate-700">{{ $use['title'] }}</span> @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                        <p class="mt-auto text-xs text-slate-500">Kullanılan görseller silinemez.</p>
                    @else
                        <span class="text-sm font-medium text-slate-500">Kullanılmıyor</span>
                        @if($file['recent'])
                            <p class="mt-auto rounded-xl bg-amber-50 p-3 text-xs text-amber-800">Yeni yükleme · İlk 1 saat silinemez.</p>
                        @elseif($pendingDelete === $file['path'])
                            <div class="mt-auto rounded-xl bg-red-50 p-3"><p class="text-xs text-red-800">Bu dosya kalıcı olarak silinecek.</p><div class="mt-3 flex flex-wrap gap-3"><button type="button" wire:click="delete" wire:loading.attr="disabled" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white">Silmeyi onayla</button><button type="button" wire:click="cancelDelete" class="px-2 py-2 text-xs text-slate-700">Vazgeç</button></div></div>
                        @else
                            <button type="button" wire:click="confirmDelete(@js($file['path']))" class="mt-auto self-start rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Görseli sil</button>
                        @endif
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Bu filtreye uygun görsel bulunamadı.</div>
        @endforelse
    </div>
    {{ $media->links() }}
</div>

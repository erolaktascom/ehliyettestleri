<div class="space-y-6">
    <x-admin.flash-messages />
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">{{ \App\Models\Advertisement::DEVICES[$device] }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    {{ $ads->total() }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Google AdSense, banner ve sponsorluk reklam alanlarını yönetin.</p>
        </div>
        <a href="{{ route('admin.advertisements.'.$device.'.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Yeni Reklam Alanı
        </a>
    </div>


    <nav class="flex gap-2" aria-label="Reklam cihazı">
        @foreach(\App\Models\Advertisement::DEVICES as $key => $label)
            <a href="{{ route('admin.advertisements.'.$key) }}" @if($device === $key) aria-current="page" @endif class="px-5 py-3 rounded-xl text-sm font-semibold border {{ $device === $key ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-600' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <p class="text-sm text-slate-500">Mobilde Header; masaüstünde Header, Sol Banner ve Sağ Banner kullanılır. Aynı alanda kitleye özel reklam, Herkes reklamına göre önceliklidir. Aynı kitlede son güncellenen aktif reklam gösterilir.</p>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Başlık veya konuma göre ara..." class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div>
                <select wire:model.live="filterLocation" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tüm Konumlar</option>
                    @foreach(\App\Models\Advertisement::locations($device) as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="filterAudience" aria-label="Hedef kitle filtresi" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                    <option value="">Tüm Kitleler</option>
                    @foreach(\App\Models\Advertisement::AUDIENCES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Başlık / Tanım</th>
                        <th class="px-5 py-3.5">Konum</th>
                        <th class="px-5 py-3.5">Hedef Kitle</th>
                        <th class="px-5 py-3.5">Durum</th>
                        <th class="px-5 py-3.5">Güncelleme</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($ads as $ad)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ $ad->title }}</td>
                            <td class="px-5 py-3.5"><span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">{{ \App\Models\Advertisement::locations($device)[$ad->location] ?? $ad->location }}</span></td>
                            <td class="px-5 py-3.5">{{ $ad->audienceLabel() }}</td>
                            <td class="px-5 py-3.5">
                                @if($ad->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Yayında</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Pasif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-400">{{ $ad->updated_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.advertisements.'.$device.'.edit', $ad->id) }}" class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Düzenle">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button" wire:click="delete({{ $ad->id }})" wire:confirm="Bu reklam alanını silmek istediğinize emin misiniz?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Reklam alanı bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ads->hasPages())
            <div class="p-4 border-t border-slate-200">{{ $ads->links() }}</div>
        @endif
    </div>
</div>

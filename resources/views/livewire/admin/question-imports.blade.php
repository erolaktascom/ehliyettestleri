<div class="space-y-6">
    <x-admin.flash-messages />
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Toplu Soru Aktarımı</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                    {{ \App\Models\QuestionImport::count() }} Aktarım
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Excel, CSV veya JSON formatında sisteme yüklenen soru aktarım kayıtları.</p>
        </div>
        <a href="{{ route('admin.question-imports.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Yeni Aktarım Kaydı
        </a>
    </div>


    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Dosya adına göre ara..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div>
                <select wire:model.live="filterStatus" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tüm Durumlar</option>
                    <option value="Tamamlandı">Tamamlandı</option>
                    <option value="Hazırlanıyor">Hazırlanıyor</option>
                    <option value="Hatalı">Hatalı</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Dosya Adı</th>
                        <th class="px-5 py-3.5">Yükleyen</th>
                        <th class="px-5 py-3.5">Durum</th>
                        <th class="px-5 py-3.5">Toplam</th>
                        <th class="px-5 py-3.5">Hazır</th>
                        <th class="px-5 py-3.5">Hatalı</th>
                        <th class="px-5 py-3.5">Aktarılan</th>
                        <th class="px-5 py-3.5">Tarih</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($imports as $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ $item->file_name }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $item->user->name ?? 'Admin' }}</td>
                            <td class="px-5 py-3.5">
                                @if($item->status === 'Tamamlandı')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> {{ $item->status }}
                                    </span>
                                @elseif($item->status === 'Hazırlanıyor')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <span class="size-1.5 rounded-full bg-amber-500"></span> {{ $item->status }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <span class="size-1.5 rounded-full bg-red-500"></span> {{ $item->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-800">{{ $item->total }}</td>
                            <td class="px-5 py-3.5 text-blue-600">{{ $item->ready }}</td>
                            <td class="px-5 py-3.5 text-red-600">{{ $item->failed }}</td>
                            <td class="px-5 py-3.5 font-bold text-emerald-600">{{ $item->imported }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-400">{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.question-imports.edit', $item->id) }}" class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Düzenle">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Bu aktarım kaydını silmek istediğinize emin misiniz?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-400">
                                Aktarım kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($imports->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $imports->links() }}
            </div>
        @endif
    </div>
</div>

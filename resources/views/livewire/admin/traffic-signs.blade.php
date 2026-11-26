<div class="space-y-6">
    <x-admin.flash-messages />
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Trafik İşaretleri</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    {{ \App\Models\TrafficSign::count() }} Levha
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Öğrenme alanındaki trafik levhalarını, görsellerini ve yayın durumunu yönetin.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.traffic-sign-categories') }}" class="px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                İşaret Kategorileri
            </a>
            <a href="{{ route('admin.traffic-signs.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Yeni Levha Ekle
            </a>
        </div>
    </div>


    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Levha adı, kod (T-1) veya anlam ara..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div>
                <select wire:model.live="selectedCategory" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tüm Kategoriler</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <select wire:model.live="publication" aria-label="Yayın durumuna göre filtrele" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                <option value="">Tüm Durumlar</option><option value="1">Yayında</option><option value="0">Taslak</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Görsel</th>
                        <th class="px-5 py-3.5">Kod</th>
                        <th class="px-5 py-3.5">Levha Adı</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5">Anlamı / Açıklaması</th>
                        <th class="px-5 py-3.5">Yayın</th>
                        <th class="px-5 py-3.5">Sıra</th>
                        <th class="px-5 py-3.5">Ekleyen</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($signs as $sign)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="size-11 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center p-1 overflow-hidden">
                                    @if($sign->image_url)
                                        <img src="{{ $sign->image_url }}" alt="{{ $sign->name }}" class="max-h-full max-w-full object-contain">
                                    @else
                                        <span class="text-xs text-slate-400" title="Görsel eklenmemiş">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-slate-100 text-slate-700">{{ $sign->code ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ $sign->name }}</td>
                            <td class="px-5 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    {{ $sign->category->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 max-w-md line-clamp-2">
                                {{ $sign->meaning }}
                            </td>
                            <td class="px-5 py-3.5"><span class="text-xs font-bold {{ $sign->is_published ? 'text-emerald-700' : 'text-slate-500' }}">{{ $sign->is_published ? 'Yayında' : 'Taslak' }}</span></td>
                            <td class="px-5 py-3.5 font-semibold text-slate-400">#{{ $sign->order }}</td>
                            <td class="px-5 py-3.5 text-xs"><x-admin.creator-name :record="$sign" /></td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.traffic-signs.edit', $sign->id) }}" class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Düzenle">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button" wire:click="delete({{ $sign->id }})" wire:confirm="Bu trafik levhasını silmek istediğinize emin misiniz?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-400">Trafik işareti bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($signs->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $signs->links() }}
            </div>
        @endif
    </div>
</div>

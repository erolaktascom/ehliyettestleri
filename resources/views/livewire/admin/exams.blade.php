<div class="space-y-6">
    <x-admin.flash-messages />
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Deneme Sınavları</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    {{ \App\Models\Exam::count() }} Sınav
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Sitede yayınlanan genel MEB deneme sınavları (Seviye 1-10).</p>
        </div>
        <a href="{{ route('admin.exams.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Yeni Sınav Ekle
        </a>
    </div>


    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Sınav başlığı veya bağlantı ara..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div>
                <select wire:model.live="selectedStatus" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tüm Durumlar</option>
                    <option value="1">Yayında</option>
                    <option value="0">Taslak</option>
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
                        <th class="px-5 py-3.5">Sıra</th>
                        <th class="px-5 py-3.5">Sınav Adı</th>
                        <th class="px-5 py-3.5">Kalıcı Bağlantı</th>
                        <th class="px-5 py-3.5">Soru / Süre</th>
                        <th class="px-5 py-3.5">Çözülme</th>
                        <th class="px-5 py-3.5">Durum</th>
                        <th class="px-5 py-3.5">Ekleyen</th>
                        <th class="px-5 py-3.5">Tarih</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($exams as $exam)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-400">#{{ $exam->order }}</td>
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900">{{ $exam->title }}</div>
                                @if($exam->description)
                                    <div class="text-xs text-slate-400 line-clamp-1 mt-0.5">{{ $exam->description }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs font-mono text-slate-500">/deneme/{{ $exam->slug }}</td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <x-admin.question-counts :published="$exam->published_questions_count" :draft="$exam->draft_questions_count" />
                                <span class="block mt-1 text-xs text-slate-500">{{ $exam->duration_minutes }} dakika</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 font-medium">
                                {{ number_format($exam->attempts_count) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <x-admin.publication-toggle :record="$exam" />
                            </td>
                            <td class="px-5 py-3.5 text-xs"><x-admin.creator-name :record="$exam" /></td>
                            <td class="px-5 py-3.5"><x-admin.record-date :record="$exam" /></td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.exams.edit', $exam->id) }}" class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Düzenle">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button" wire:click="delete({{ $exam->id }})" wire:confirm="Bu sınavı silmek istediğinize emin misiniz?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-400">Sınav bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($exams->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $exams->links() }}
            </div>
        @endif
    </div>
</div>

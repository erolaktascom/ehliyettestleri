<div class="space-y-6">
    <x-admin.flash-messages />
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Soru Havuzu</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    {{ \App\Models\Question::count() }} Soru
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Sınav ve testlerde çıkan MEB ehliyet sorularını yönetin, ekleyin ve düzenleyin.</p>
        </div>
        <a href="{{ route('admin.questions.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Yeni Soru Ekle
        </a>
    </div>


    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-2">
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Soru metni veya ID ile ara (örn. 591)..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div>
                <select wire:model.live="selectedTopic" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tüm Kategoriler</option>
                    @foreach($topics as $topic)
                        <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="selectedStatus" aria-label="Yayın durumu" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tüm Durumlar</option>
                    <option value="Yayında">Yayında</option>
                    <option value="Taslak">Taslak</option>
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
                        <th class="px-5 py-3.5">ID</th>
                        <th class="px-5 py-3.5">Soru Metni</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5">Tür</th>
                        <th class="px-5 py-3.5">Durum</th>
                        <th class="px-5 py-3.5">Ekleyen</th>
                        <th class="px-5 py-3.5">Tarih</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($questions as $question)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-400">#{{ $question->id }}</td>
                            <td class="px-5 py-3.5 max-w-md">
                                <div class="font-medium text-slate-900 line-clamp-2">{{ strip_tags($question->question_text) }}</div>
                                @if(preg_match('/<img\b/i', $question->question_text))
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 mt-1">
                                        <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Görselli Soru
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    {{ $question->topicCategory?->name ?? 'Genel' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                {{ $question->type }}
                            </td>
                            <td class="px-5 py-3.5"><x-admin.publication-toggle :record="$question" /></td>
                            <td class="px-5 py-3.5 text-xs"><x-admin.creator-name :record="$question" /></td>
                            <td class="px-5 py-3.5"><x-admin.record-date :record="$question" /></td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.questions.edit', $question->id) }}"
                                   class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                   title="Düzenle">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button"
                                        wire:click="delete({{ $question->id }})"
                                        wire:confirm="Bu soruyu silmek istediğinize emin misiniz?"
                                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                                        title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-slate-400">
                                Soru bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($questions->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
</div>

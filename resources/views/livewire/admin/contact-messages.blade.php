<div>
    <x-admin.flash-messages />
    <!-- Header Controls -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
            <div class="sm:col-span-2 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="İsim, e-posta veya konuda ara..."
                    class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent placeholder:text-slate-400">
                <svg class="size-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent text-slate-700">
                    <option value="">Tüm Durumlar</option>
                    <option value="unread">Okunmamış</option>
                    <option value="read">Okunmuş</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Messages Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Gönderen</th>
                        <th class="px-4 py-3.5">Konu</th>
                        <th class="px-4 py-3.5">Mesaj Özeti</th>
                        <th class="px-4 py-3.5">Tarih</th>
                        <th class="px-4 py-3.5">Durum</th>
                        <th class="px-4 py-3.5 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($messages as $m)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">
                                {{ $m->name }}
                                <div class="text-xs text-slate-400 font-normal">{{ $m->email }}</div>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-800">
                                <a href="{{ route('admin.contact-messages.show', $m->id) }}" class="text-blue-700 hover:underline">{{ $m->subject ?: 'Konu Belirtilmemiş' }}</a>
                            </td>
                            <td class="px-4 py-3.5 max-w-xs text-slate-600 truncate">
                                {{ $m->message }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                {{ $m->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded text-[11px] font-extrabold {{ !$m->is_read ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ !$m->is_read ? 'Okunmadı' : 'Okundu' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <button wire:click.stop="delete({{ $m->id }})" wire:confirm="Bu mesajı silmek istediğinize emin misiniz?" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400 font-medium">Gelen mesaj bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($messages->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $messages->links() }}
            </div>
        @endif
    </div>

</div>

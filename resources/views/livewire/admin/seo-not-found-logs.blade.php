<div class="space-y-6">
    <x-admin.flash-messages />
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">SEO Ayarları</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                    {{ \App\Models\SeoNotFoundLog::count() }} Kayıt
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Kullanıcıların veya botların ulaşıp 404 aldığı eksik adresler listesi.</p>
        </div>
        <div class="flex items-center gap-2">

            <button wire:click="clearAll" wire:confirm="Tüm 404 kayıtlarını temizlemek istediğinize emin misiniz?"
                    class="px-4 py-2.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 font-semibold text-sm transition">
                Tümünü Temizle
            </button>
        </div>
    </div>

    <x-admin.seo-tabs active="seo-not-found-logs" />

    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Bulunamayan URL ara..." class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Bulunamayan Adres (URL)</th>
                        <th class="px-5 py-3.5">İstek Sayısı</th>
                        <th class="px-5 py-3.5">İlk Görülme</th>
                        <th class="px-5 py-3.5">Son Görülme</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-mono text-xs text-rose-600 font-semibold">{{ $log->url }}</td>
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ number_format($log->request_count) }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-400">{{ $log->first_seen_at?->format('d.m.Y H:i') ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-400">{{ $log->last_seen_at?->format('d.m.Y H:i') ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <button wire:click="delete({{ $log->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">404 kaydı bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-200">{{ $logs->links() }}</div>
        @endif
    </div>
</div>

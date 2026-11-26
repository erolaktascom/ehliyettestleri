<div class="space-y-6">
    <x-admin.flash-messages />
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Yönetim Ekibi</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                    {{ \App\Models\User::where('is_admin', true)->count() }} Panel Kullanıcısı
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Panel erişimi olan Yönetici ve Editör hesapları.</p>
        </div>
        <a href="{{ route('admin.staff-users.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Yeni Panel Kullanıcısı Ekle
        </a>
    </div>


    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="İsim veya e-posta ara..." class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Ad Soyad</th>
                        <th class="px-5 py-3.5">E-posta</th>
                        <th class="px-5 py-3.5">Rol</th>
                        <th class="px-5 py-3.5">Kayıt Tarihi</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staff as $u)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded-full bg-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($u->name, 0, 1) }}
                                    </div>
                                    <div class="font-bold text-slate-900">{{ $u->name }}</div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $u->email }}</td>
                            <td class="px-5 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    {{ $u->role_name ?? 'Yönetici' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-400">{{ $u->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.staff-users.edit', $u->id) }}" class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Düzenle">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                @if(auth()->id() !== $u->id)
                                    <button wire:click="delete({{ $u->id }})" wire:confirm="Bu yöneticiyi silmek istediğinize emin misiniz?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Sil">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Yönetici bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($staff->hasPages())
            <div class="p-4 border-t border-slate-200">{{ $staff->links() }}</div>
        @endif
    </div>
</div>

<div>
    <x-admin.flash-messages />


    <!-- Header Controls -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex-1">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="İsim veya e-posta ile ara..."
                    class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent placeholder:text-slate-400">
                <svg class="size-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

        </div>

        <a href="{{ route('admin.users.create') }}"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white font-bold text-sm shadow-md shadow-navy-900/10 transition shrink-0 active:scale-95">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Kullanıcı Ekle</span>
        </a>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Kullanıcı</th>
                        <th class="px-4 py-3.5">Rol</th>
                        <th class="px-4 py-3.5">Hedef Ehliyet</th>
                        <th class="px-4 py-3.5">Sınav Sayısı</th>
                        <th class="px-4 py-3.5">Kayıt Tarihi</th>
                        <th class="px-4 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="size-9 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center border border-slate-200">
                                        {{ $u->initials }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $u->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Üye</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-700">
                                    {{ $u->target_license_class ?? 'B' }} Sınıfı
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold text-slate-800 text-xs">{{ $u->attempts_count }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-500">
                                {{ $u->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.users.edit', $u->id) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" title="Düzenle">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if($u->id !== auth()->id())
                                        <button wire:click="delete({{ $u->id }})" wire:confirm="Bu kullanıcıyı silmek istediğinize emin misiniz?" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Sil">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400 font-medium">Üye / öğrenci bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

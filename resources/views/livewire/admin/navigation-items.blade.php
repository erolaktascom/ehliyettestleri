<div class="space-y-6">
    <x-admin.flash-messages />
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">{{ \App\Models\NavigationItem::ADMIN_MENUS[$menu] }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                    {{ $items->total() }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $menu === 'footer-bottom' ? 'Telif hakkının yanındaki Gizlilik Politikası, Kullanım Koşulları ve İletişim gibi bağlantıları yönetin.' : 'Bu menünün bağlantılarını, sırasını ve görünürlüğünü diğer menülerden bağımsız yönetin.' }}</p>
        </div>
        <a href="{{ route('admin.navigation-items.'.$menu.'.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Yeni Menü Öğesi Ekle
        </a>
    </div>


    <nav class="flex flex-wrap gap-2" aria-label="Menü seçimi">
        @foreach(\App\Models\NavigationItem::ADMIN_MENUS as $key => $label)
            <a href="{{ route('admin.navigation-items.'.$key) }}" @if($menu === $key) aria-current="page" @endif class="px-5 py-3 rounded-xl text-sm font-semibold border {{ $menu === $key ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
        @endforeach
    </nav>
    @if(in_array($menu, ['footer', 'apps']))
        <section class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <div><h2 class="font-bold text-slate-900">{{ $menu === 'apps' ? 'Uygulamalar Sütunu' : 'Alt Bilgi Sütunları' }}</h2>
                    <p class="text-sm text-slate-500 mt-1">Sütunları düzenleyin, gizleyin veya sıralayın. Gizlenen ve boş sütunlar sitede yer kaplamaz.</p></div>
                @if($menu === 'footer' || $footerGroups->isEmpty())
                    <a class="rounded-xl px-4 py-2 text-sm font-semibold bg-blue-50 text-blue-700" href="{{ route('admin.navigation-items.'.$menu.'.columns.create') }}">+ Sütun Ekle</a>
                @endif
            </div>
            @if($pendingGroup)
                <div class="rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
                    <p class="text-sm text-red-800"><strong>{{ $pendingGroup->name }}</strong> sütunu ve içindeki <strong>{{ $pendingGroup->items_count }} bağlantı</strong> silinecek. Bağlantıları korumak için silmek yerine sütunu gizleyebilirsiniz.</p>
                    <div class="flex flex-wrap gap-3 mt-3">
                        <button type="button" wire:click="deleteGroup" wire:loading.attr="disabled" class="rounded-lg bg-red-600 text-white px-4 py-2 text-sm font-semibold">Sütunu ve Bağlantıları Sil</button>
                        <button type="button" wire:click="cancelGroupDeletion" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm">Vazgeç</button>
                    </div>
                </div>
            @endif
            <div class="divide-y divide-slate-100">
                @forelse($footerGroups as $group)
                    <div wire:key="footer-group-{{ $group->id }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 py-4">
                        <div><span class="text-xs text-slate-400 mr-2">#{{ $group->order }}</span><strong class="text-sm text-slate-900">{{ $group->name }}</strong>
                            <span class="text-xs text-slate-500 ml-2">{{ $group->items_count }} bağlantı</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                            <button type="button" wire:click="moveGroup({{ $group->id }}, 'up')" aria-label="{{ $group->name }} sütununu yukarı taşı" class="p-2 rounded-lg border border-slate-200">↑</button>
                            <button type="button" wire:click="moveGroup({{ $group->id }}, 'down')" aria-label="{{ $group->name }} sütununu aşağı taşı" class="p-2 rounded-lg border border-slate-200">↓</button>
                            <button type="button" wire:click="toggleGroup({{ $group->id }})" class="px-3 py-2 rounded-lg {{ $group->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $group->is_active ? 'Görünür · Gizle' : 'Gizli · Göster' }}</button>
                            <a href="{{ route('admin.navigation-items.'.$menu.'.columns.edit', $group->id) }}" class="px-3 py-2 text-blue-600 rounded-lg bg-blue-50">Düzenle</a>
                            <button type="button" wire:click="requestGroupDeletion({{ $group->id }})" class="px-3 py-2 rounded-lg text-red-600 bg-red-50">Sil</button>
                        </div>
                    </div>
                @empty
                    <p class="py-5 text-sm text-slate-500">Henüz sütun yok. Bağlantı eklemek için önce bir sütun oluşturun.</p>
                @endforelse
            </div>
        </section>
    @endif
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Menü adı veya bağlantı ara..." class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="size-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Sıra</th>
                        <th class="px-5 py-3.5">Menü Adı</th>
                        @if($menu === 'footer') <th class="px-5 py-3.5">Konum</th> @endif
                        <th class="px-5 py-3.5">Bağlantı</th>
                        <th class="px-5 py-3.5">Kitle</th>
                        <th class="px-5 py-3.5">Durum</th>
                        <th class="px-5 py-3.5 text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $nav)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-medium text-slate-400">#{{ $nav->order }}</td>
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ $nav->name }}</td>
                            @if($menu === 'footer') <td class="px-5 py-3.5">{{ $footerGroups->firstWhere('key', $nav->footer_group)?->name ?? 'En Alt Menü' }}</td> @endif
                            <td class="px-5 py-3.5 text-xs font-mono text-slate-500">{{ $nav->url }}</td>
                            <td class="px-5 py-3.5"><span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">{{ $nav->audience }}</span></td>
                            <td class="px-5 py-3.5">
                                @if($nav->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Pasif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.navigation-items.'.$menu.'.edit', $nav->id) }}" class="inline-flex p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Düzenle">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button" wire:click="delete({{ $nav->id }})" wire:confirm="Bu menü öğesini silmek istediğinize emin misiniz?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Sil">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $menu === 'footer' ? 7 : 6 }}" class="px-5 py-10 text-center text-slate-400">Menü öğesi bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="p-4 border-t border-slate-200">{{ $items->links() }}</div>
        @endif
    </div>
</div>

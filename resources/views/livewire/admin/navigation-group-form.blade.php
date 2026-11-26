<div class="max-w-3xl space-y-6">
    <x-admin.flash-messages />
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.navigation-items.'.$menu) }}" aria-label="Listeye dön" class="px-3 py-2 bg-white border border-slate-200 rounded-xl">←</a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $groupId ? 'Sütunu Düzenle' : 'Yeni Sütun Ekle' }}</h1>
    </div>
    <form wire:submit="save" class="bg-white p-6 rounded-2xl border border-slate-200 space-y-5">
        <div><label for="group-name" class="block text-sm font-semibold mb-2">Sütun Başlığı</label>
            <input id="group-name" wire:model="name" maxlength="100" class="w-full border border-slate-200 rounded-xl px-3 py-2.5">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div><label for="group-order" class="block text-sm font-semibold mb-2">Sıralama</label>
            <input id="group-order" type="number" min="0" max="100000" wire:model="order" class="w-full border border-slate-200 rounded-xl px-3 py-2.5">
            <p class="text-xs text-slate-500 mt-1">Uygulamalar dahil tüm alt bilgi sütunları arasında uygulanır. Küçük sayı önce gösterilir.</p>
            @error('order')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> <span class="text-sm font-semibold">Sitede Göster</span></label>
        <p class="text-xs text-slate-500">Sütunu gizlemek içindeki bağlantıları silmez. Görünür bağlantısı olmayan sütun sitede boş alan oluşturmaz.</p>
        <div class="text-right"><button type="submit" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-5 py-2.5 font-semibold">{{ $groupId ? 'Değişiklikleri Kaydet' : 'Sütunu Oluştur' }}</button></div>
    </form>
</div>

<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.question-topics') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $topicId ? 'Soru Kategorisini Düzenle' : 'Yeni Soru Kategorisi Ekle' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">Soruların ve konu testlerinin kategorilerini yönetin.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kategori Adı *</label>
                <input type="text" wire:model.live="name" placeholder="Örn: İlk Yardım Bilgisi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kalıcı Bağlantı (Slug) *</label>
                <input type="text" wire:model="slug" placeholder="ilk-yardim-bilgisi" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('slug') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sıralama *</label>
                <input type="number" wire:model="order" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('order') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <x-admin.category-icon-select :icon="$icon" :name="$name" />
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Açıklama</label>
            <textarea wire:model="description" rows="3" placeholder="Kategori açıklaması..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            @error('description') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer hover:bg-slate-50">
                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-blue-600 size-4">
                <div>
                    <span class="text-sm font-semibold text-slate-800 block">Aktif Durumda</span>
                    <span class="text-xs text-slate-500">Kategori öğrencilere ve soru ekleme ekranında görünür.</span>
                </div>
            </label>
        </div>

        </div>
        <x-admin.seo-fields :custom="filled($seo_title) || filled($seo_description)" />
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                {{ $topicId ? 'Değişiklikleri Kaydet' : 'Kategoriyi Oluştur' }}
            </button>
        </div>
    </form>
</div>

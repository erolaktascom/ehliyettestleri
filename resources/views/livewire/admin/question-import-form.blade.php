<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.question-imports') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $importId ? 'Soru Aktarım Kaydını Düzenle' : 'Yeni Soru Aktarım Kaydı' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">Toplu soru yükleme (Excel, CSV, JSON) işlem detayları.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Dosya Adı *</label>
                <input type="text" wire:model="file_name" placeholder="ornek-sorular-2026.xlsx" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('file_name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Durum *</label>
                <select wire:model="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Tamamlandı">Tamamlandı</option>
                    <option value="Hazırlanıyor">Hazırlanıyor</option>
                    <option value="Hatalı">Hatalı</option>
                </select>
                @error('status') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Toplam Soru</label>
                <input type="number" wire:model="total" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('total') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Hazır</label>
                <input type="number" wire:model="ready" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('ready') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Hatalı</label>
                <input type="number" wire:model="failed" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('failed') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Aktarılan</label>
                <input type="number" wire:model="imported" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('imported') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                {{ $importId ? 'Değişiklikleri Kaydet' : 'Aktarımı Başlat / Kaydet' }}
            </button>
        </div>
    </form>
</div>

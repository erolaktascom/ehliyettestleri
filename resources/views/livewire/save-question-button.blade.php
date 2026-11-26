<button wire:click="toggleSave" type="button"
    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-sm {{ $isSaved ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' }}"
    title="{{ $isSaved ? 'Kaydedilenlerden Çıkar' : 'Soruyu Kaydet' }}">
    <svg class="size-4 shrink-0 {{ $isSaved ? 'fill-amber-500 text-amber-500' : 'text-slate-400' }}" viewBox="0 0 24 24" stroke="currentColor" fill="none">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
    </svg>
    <span>{{ $isSaved ? 'Kaydedildi' : 'Soruyu Kaydet' }}</span>
</button>

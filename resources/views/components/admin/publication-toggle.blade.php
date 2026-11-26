@props(['record'])
@php($published = $record instanceof \App\Models\Question ? $record->status === 'Yayında' : (bool) $record->is_published)
<button type="button" wire:click="toggleActive({{ $record->id }})"
        wire:loading.attr="disabled" wire:target="toggleActive({{ $record->id }})"
        aria-pressed="{{ $published ? 'true' : 'false' }}"
        title="{{ $published ? 'Taslağa al' : 'Yayımla' }}"
        class="inline-flex items-center gap-1.5 whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 {{ $published ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
    <span class="size-1.5 rounded-full {{ $published ? 'bg-emerald-500' : 'bg-slate-400' }}" aria-hidden="true"></span>
    {{ $record instanceof \App\Models\BlogPost && $record->isScheduled() ? 'Planlandı' : ($published ? 'Yayında' : 'Taslak') }}
</button>

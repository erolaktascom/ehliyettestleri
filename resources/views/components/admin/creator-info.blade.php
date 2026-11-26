@props(['record'])
<div>
    <span class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Ekleyen</span>
    <div class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700">
        @if($record)
        <x-admin.creator-name :record="$record" />
        @else
            {{ auth()->user()?->name }}
        @endif
    </div>
</div>

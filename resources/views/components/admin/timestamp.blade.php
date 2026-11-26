@props(['date'])
@if($date)
    @php($localDate = $date->copy()->timezone('Europe/Istanbul'))
    <time datetime="{{ $localDate->toIso8601String() }}" title="Türkiye saati" class="block whitespace-nowrap text-xs">
        <span class="block text-slate-600">{{ $localDate->format('d.m.Y') }}</span>
        <span class="block text-slate-400 mt-0.5">{{ $localDate->format('H:i') }}</span>
    </time>
@else
    <span class="text-xs text-slate-400">—</span>
@endif

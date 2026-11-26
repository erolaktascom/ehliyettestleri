@php
    $lastRun = \Illuminate\Support\Facades\Cache::get('notifications.last_run_at');
    $lastRunAt = $lastRun ? \Carbon\Carbon::parse($lastRun) : null;
    $running = $lastRunAt && $lastRunAt->gt(now()->subMinutes(3));
@endphp
<div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
    <strong class="{{ $running ? 'text-emerald-700' : 'text-amber-700' }}">{{ $running ? 'Otomatik kontrol çalışıyor' : 'Otomatik kontrol bekleniyor' }}</strong>
    <span class="ml-2">Son kontrol: {{ $lastRunAt?->format('d.m.Y H:i') ?? 'Henüz yapılmadı' }}</span>
    @unless($running)<p class="text-xs mt-1">Zamanlayıcı çalışmıyorsa planlı gönderimler bekler.</p>@endunless
</div>

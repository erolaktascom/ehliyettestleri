@props(['total', 'completed', 'unit' => 'Test', 'totalLabel' => 'Toplam Test', 'completedLabel' => 'Tamamlanan', 'icon' => 'exam'])
<div class="stitch-row flex items-center gap-3 sm:gap-4 px-3 py-3 sm:px-4 sm:py-4 shrink-0 bg-slate-50 self-start sm:self-auto" aria-label="Çalışma özeti">
    <div class="flex items-center gap-2 sm:gap-3">
        <span class="stitch-icon-box size-10 sm:size-11 bg-white shrink-0"><x-student-icon :name="$icon" class="size-6" /></span>
        <div><small class="block text-muted text-xs font-semibold uppercase">{{ $totalLabel }}</small><strong class="text-lg sm:text-xl whitespace-nowrap text-ink">{{ $total }} {{ $unit }}</strong></div>
    </div>
    @auth
    <div class="h-10 w-px bg-slate-200" aria-hidden="true"></div>
    <div><small class="block text-muted text-xs font-semibold uppercase">{{ $completedLabel }}</small><strong class="text-lg sm:text-xl whitespace-nowrap text-brand-700">{{ $completed }} {{ $unit }}</strong></div>
    @endauth
</div>

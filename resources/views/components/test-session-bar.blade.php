@props(['title', 'exitUrl', 'timed' => false, 'sectionLabel' => null])

<header aria-label="Test araç çubuğu" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur shadow-sm">
    <div class="site-container flex items-center gap-3 py-3 sm:gap-5 sm:py-4">
        <a href="{{ $exitUrl }}" @click.prevent="requestLeave($el.href)"
           :aria-disabled="isLeaving || isFinishing" :class="{ 'opacity-50 pointer-events-none': isLeaving || isFinishing }"
           class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-700 hover:border-red-300 hover:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition"
           aria-label="Testten çık">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l-5-5 5-5M5 12h14" /></svg>
            Çıkış
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-base font-extrabold text-ink sm:text-lg" title="{{ $title }}">{{ $title }}</h1>
            <div class="mt-2 flex items-center gap-2">
            <div role="progressbar" aria-label="Cevaplama ilerlemesi" aria-valuemin="0" aria-valuemax="100"
                 :aria-valuenow="totalQuestions > 0 ? Math.round(answeredCount / totalQuestions * 100) : 0"
                 :aria-valuetext="answeredCount + ' / ' + totalQuestions + ' soru cevaplandı'"
                 :title="answeredCount + ' / ' + totalQuestions + ' soru cevaplandı'"
                 class="min-w-0 flex-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-brand-700 transition-[width] duration-300 motion-reduce:transition-none"
                     :style="{ width: (totalQuestions > 0 ? Math.min(100, answeredCount / totalQuestions * 100) : 0) + '%' }"></div>
            </div>
                <span class="shrink-0 text-xs font-bold text-brand-700 tabular-nums" aria-hidden="true"
                      x-text="'%' + (totalQuestions > 0 ? Math.min(100, Math.round(answeredCount / totalQuestions * 100)) : 0)">%0</span>
            </div>
        </div>
        <div class="ml-auto flex shrink-0 flex-col items-end gap-1.5 sm:flex-row sm:items-center sm:gap-3">
            <span class="rounded-xl bg-brand-50 px-3 py-1.5 text-[11px] font-bold text-brand-700 sm:py-2 sm:text-xs">{{ $sectionLabel ?? ($timed ? 'Deneme Sınavı' : 'Konu Testi') }}</span>
            @if($timed)
                <div aria-label="Kalan süre" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                     :class="{ 'bg-red-50 border-red-200 text-red-700': isUrgent }">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="hidden text-xs font-semibold sm:inline">Kalan süre</span>
                    <span class="font-mono text-sm font-bold tabular-nums sm:text-base" x-text="formattedTime">--:--</span>
                </div>
            @else
                <span class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Süresiz</span>
            @endif
        </div>
    </div>
</header>

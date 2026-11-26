<button type="button" x-ref="questionMapToggle"
        class="lg:hidden inline-flex items-center justify-center gap-1.5 rounded-xl border border-brand-200 bg-brand-50 px-2.5 text-xs font-bold text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-700"
        style="min-height:44px" @click="mapExpanded = !mapExpanded"
        :aria-expanded="mapExpanded" aria-controls="mobile-question-map"
        :disabled="isFinishing || isLeaving">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Soru Haritası
</button>

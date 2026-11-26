@props(['questions', 'mobile' => false])
<aside @class([
    'lg:hidden mb-6 rounded-xl border border-slate-200 bg-slate-50 p-3' => $mobile,
    'hidden lg:block site-card p-5 sm:p-6 lg:sticky lg:top-[100px]' => !$mobile,
])
    @if($mobile)
        id="mobile-question-map" x-cloak x-show="mapExpanded"
        @keydown.escape.stop.prevent="mapExpanded = false; $refs.questionMapToggle.focus()"
    @endif
    aria-label="Soru haritası">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-extrabold text-sm text-ink">Soru Haritası</h2>
        <span class="text-xs font-bold text-brand-700"><span x-text="answeredCount"></span>/<span x-text="totalQuestions"></span></span>
    </div>
        <div class="grid grid-cols-5 sm:grid-cols-6 lg:grid-cols-5 gap-2 mt-4 lg:mt-0">
            @foreach($questions as $index => $q)
                <button type="button" @click="goToQuestion({{ $index }}); {{ $mobile ? '$nextTick(() => $refs.questionMapToggle.focus())' : '' }}" :disabled="isFinishing || isLeaving"
                        style="min-height:44px" class="aspect-square rounded-xl font-bold text-xs flex items-center justify-center transition border"
                        :aria-current="currentQuestionIndex === {{ $index }} ? 'step' : null"
                        :aria-label="'Soru {{ $index + 1 }} — ' + (answers[{{ $q->id }}] ? 'Cevaplandı' : 'Boş')"
                        :class="{
                            'ring-2 ring-brand-700 ring-offset-2': currentQuestionIndex === {{ $index }},
                            'bg-brand-700 text-white border-brand-800 shadow-sm': answers[{{ $q->id }}],
                            'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100': !answers[{{ $q->id }}]
                        }">{{ $index + 1 }}</button>
            @endforeach
        </div>
</aside>

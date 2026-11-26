@extends('layouts.app')

@section('title', $exam->title . ' - ' . $siteName)

@section('test-session', '1')

@section('content')
<div x-data="examRunner({
    totalQuestions: {{ $questions->count() }},
    @if(isset($gradeUrl))
    gradeUrl: @js($gradeUrl),
    sessionTokenUrl: @js(route('guest.session-token')),
    remainingSeconds: {{ (int) $remainingSeconds }},
    expiresAt: {{ now()->addSeconds($remainingSeconds)->valueOf() }},
    serverNow: {{ now()->valueOf() }},
    csrfToken: @js(csrf_token()),
    savedQuestionIds: [],
    @else
    remainingSeconds: {{ (int) $remainingSeconds }},
    expiresAt: {{ $attempt->expires_at?->valueOf() ?? now()->valueOf() }},
    serverNow: {{ now()->valueOf() }},
    saveAnswerUrl: '{{ route('exams.save-answer', $attempt) }}',
    finishUrl: '{{ route('exams.finish', $attempt) }}',
    leaveUrl: '{{ route('exams.leave', $attempt) }}',
    positionUrl: '{{ route('exams.position', $attempt) }}',
    initialQuestionIndex: {{ min((int) ($attempt->current_question_index ?? 0), max(0, $questions->count() - 1)) }},
    freshStart: {{ session()->pull('new_attempt_id') === (string) $attempt->id ? 'true' : 'false' }},
    resumeAllowed: {{ $attempt->topic_test_id && $attempt->user_id ? 'true' : 'false' }},
    csrfToken: '{{ csrf_token() }}',
    initialAnswers: {{ json_encode($answers->mapWithKeys(fn($a) => [$a->question_id => $a->selected_option])) }},
    savedQuestionIds: {{ json_encode($savedQuestionIds) }},
    toggleSaveUrl: '{{ url('/soru') }}'
    @endif
})" class="pb-6 sm:pb-10">
    @if(isset($gradeUrl))
    <section x-cloak x-show="resultHtml" x-html="resultHtml" class="py-6"></section>
    <div x-show="!resultHtml">
    @endif
    <x-test-session-bar :title="$exam->title" :exit-url="route('exams.index')" :timed="true" />
    <div x-cloak x-show="saveStatus" role="alert" class="site-container mb-4 text-sm font-semibold text-red-700">
        <span x-text="saveStatus"></span>
        @if(isset($gradeUrl))
            <button type="button" @click="finishExam()" :disabled="isFinishing" class="ml-3 underline">Sonucu yeniden getir</button>
        @else
        <button type="button" @click="retryAnswers()" x-show="Object.keys(failedAnswers).length > 0 || failedPosition !== null" :disabled="pendingSaves > 0 || isFinishing || isLeaving" class="ml-3 underline disabled:opacity-50">Yeniden Kaydet</button>
        @endif
    </div>
    <p x-cloak x-show="bookmarkError" x-text="bookmarkError" role="alert" class="site-container mb-4 text-sm font-semibold text-red-700"></p>
    <div class="site-container">


        <!-- Main Exam Workspace Layout -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6 items-start">
            <!-- Left: Question Card -->
            <div class="site-card p-6 sm:p-8 relative">
                <!-- Question Number & Status -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-6">
                    <div class="flex items-center gap-2.5">
                        <span class="grid size-9 place-items-center rounded-xl bg-brand-700 text-white font-extrabold text-sm" x-text="currentQuestionIndex + 1"></span>
                        <span class="text-xs font-bold text-muted">/ <span x-text="totalQuestions"></span> Soru</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-question-bookmark question-expression="questionsList[currentQuestionIndex]" />
                        <x-test-question-map-toggle />
                    </div>
                </div>

                <!-- Active Question Content -->
                <x-test-question-map :questions="$questions" :mobile="true" />

                @foreach($questions as $index => $q)
                    <div x-show="currentQuestionIndex === {{ $index }}" x-cloak>
                        <!-- Question Media (if any) -->

                        <!-- Question Text -->
                        <div class="text-base sm:text-lg font-bold text-ink leading-relaxed mb-6">
                            {!! \App\Support\QuestionMedia::clean($q->question_text) !!}
                        </div>

                        <!-- Options Form -->
                        <div class="space-y-3">
                            @foreach($q->options_list as $optKey => $optText)
                                <label class="flex items-start gap-3.5 p-4 rounded-xl border border-slate-200 transition cursor-pointer select-none"
                                       :class="answers[{{ $q->id }}] === '{{ $optKey }}' ? 'bg-brand-50/80 border-brand-500 ring-2 ring-brand-200' : 'hover:bg-slate-50 hover:border-slate-300'">
                                    <input type="radio"
                                           :disabled="isFinishing || isLeaving || remainingSeconds <= 0"
                                           autocomplete="off"
                                           name="question_{{ $q->id }}"
                                           value="{{ $optKey }}"
                                           class="sr-only"
                                           @change="selectAnswer({{ $q->id }}, '{{ $optKey }}')"
                                           :checked="answers[{{ $q->id }}] === '{{ $optKey }}'">
                                    
                                    <span class="grid size-8 shrink-0 place-items-center rounded-lg font-bold text-sm"
                                          :class="answers[{{ $q->id }}] === '{{ $optKey }}' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-700'">
                                        {{ $optKey }}
                                    </span>
                                    <span class="text-sm sm:text-base font-medium text-slate-800 leading-normal pt-1">
                                        {{ $optText }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- Prev / Next Navigation Controls -->
                <div class="study-navigation mt-8 pt-6 border-t border-slate-100 flex items-center justify-between gap-3">
                    <button type="button"
                            @click="prevQuestion()"
                            :disabled="currentQuestionIndex === 0"
                            class="site-button-secondary py-2.5 px-5 disabled:opacity-40 disabled:cursor-not-allowed">
                        <x-action-label icon="arrow-left">Önceki Soru</x-action-label>
                    </button>

                    <button type="button"
                            @click="currentQuestionIndex === totalQuestions - 1 ? confirmFinishModal = true : nextQuestion()"
                            :disabled="isFinishing || isLeaving"
                            
                            class="site-button-primary py-2.5 px-6 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="site-action-label"><x-student-icon name="arrow" x-show="currentQuestionIndex !== totalQuestions - 1" /><x-student-icon name="flag" x-cloak x-show="currentQuestionIndex === totalQuestions - 1" /><span x-text="currentQuestionIndex === totalQuestions - 1 ? 'Sınavı Bitir' : 'Sonraki Soru'">Sonraki Soru</span></span>
                    </button>
                </div>
            </div>

            <x-test-question-map :questions="$questions" />
        </div>
    </div>

    <x-test-session-dialogs :attempt="$attempt" :is-exam="true" />
    @unless(isset($gradeUrl))
    <!-- Hidden auto-finish form -->
    <form id="autoFinishForm" method="POST" action="{{ route('exams.finish', $attempt) }}" class="hidden">
        @csrf
    </form>
    @endunless
@if(isset($gradeUrl))
    </div>
@endif
</div>
@unless(isset($gradeUrl))
<form id="restart-attempt-form" method="POST" action="{{ route('exams.start', $exam) }}" hidden>@csrf</form>
@endunless
@endsection

@push('scripts')
<script>
    function examRunner(config) {
        return {
            totalQuestions: config.totalQuestions,
            currentQuestionIndex: config.initialQuestionIndex || 0,
            remainingSeconds: config.remainingSeconds,
            timerInterval: null,
            confirmFinishModal: false,
            mapExpanded: false,
            @include('partials.exam-answer-actions')
            ...questionBookmarks(config),
            questionsList: @json($questions->pluck('id')),

            init() {
                if (!this.prepareAttempt()) return;
                this.startTimer();
            },

            clockOffset: 0,
            visibilityHandler: null,

            startTimer() {
                this.clockOffset = Number(config.serverNow) - Date.now();
                this.updateRemainingTime();
                if (this.remainingSeconds > 0) {
                    this.timerInterval = setInterval(() => this.updateRemainingTime(), 1000);
                }
                this.visibilityHandler = () => {
                    if (document.visibilityState === 'visible') this.updateRemainingTime();
                };
                document.addEventListener('visibilitychange', this.visibilityHandler);
            },

            updateRemainingTime() {
                this.remainingSeconds = Math.max(0, Math.ceil((Number(config.expiresAt) - (Date.now() + this.clockOffset)) / 1000));
                if (this.remainingSeconds === 0) {
                    clearInterval(this.timerInterval);
                    if (!this.isFinishing) this.finishExam(document.getElementById('autoFinishForm'), true);
                }
            },

            destroy() {
                clearInterval(this.timerInterval);
                if (this.visibilityHandler) document.removeEventListener('visibilitychange', this.visibilityHandler);
            },

            get formattedTime() {
                const total = Math.max(0, Math.floor(this.remainingSeconds));
                const m = Math.floor(total / 60);
                const s = total % 60;
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            },

            get isUrgent() {
                return this.remainingSeconds <= 300; // last 5 minutes
            },

            get answeredCount() {
                return Object.keys(this.answers).length;
            },

            goToQuestion(index) {
                if (index >= 0 && index < this.totalQuestions) {
                    this.mapExpanded = false;
                    this.currentQuestionIndex = index;
                    this.savePosition(index);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            nextQuestion() {
                if (this.currentQuestionIndex < this.totalQuestions - 1) {
                    this.goToQuestion(this.currentQuestionIndex + 1);
                }
            },

            prevQuestion() {
                if (this.currentQuestionIndex > 0) {
                    this.goToQuestion(this.currentQuestionIndex - 1);
                }
            },

        };
    }
</script>
@endpush

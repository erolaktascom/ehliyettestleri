    <div class="site-container">

<!-- Results Summary Card -->
        <div class="site-card p-6 sm:p-10 text-center relative overflow-hidden border-2 {{ $attempt->is_passed ? 'border-emerald-500/40 bg-gradient-to-b from-emerald-50/50 to-white' : 'border-red-500/40 bg-gradient-to-b from-red-50/50 to-white' }}">
            <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs sm:text-sm font-extrabold uppercase tracking-wide {{ $attempt->is_passed ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                {{ $attempt->is_passed ? '✓ BAŞARILI (GEÇTİNİZ)' : '✕ BAŞARISIZ (KALDINIZ)' }}
            </span>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-ink mt-4">
                {{ $attempt->is_passed ? 'Tebrikler! Sınavı Başarıyla Geçtiniz' : 'Daha Çok Pratik Yapmalısınız' }}
            </h1>
            <p class="text-sm sm:text-base text-muted mt-2 max-w-lg mx-auto">
                {{ $attempt->exam ? $attempt->exam->title : ($attempt->topicTest ? $attempt->topicTest->title : 'Deneme Sınavı') }} tamamlandı. Aşağıda detaylı soru analizini inceleyebilirsiniz.
            </p>

            <!-- Score Circular Display -->
            <div class="mt-8 flex flex-col items-center">
                <div class="size-36 rounded-full border-[8px] flex flex-col items-center justify-center shadow-lg {{ $attempt->is_passed ? 'border-emerald-500 text-emerald-700 bg-white' : 'border-red-500 text-red-700 bg-white' }}">
                    <span class="text-4xl font-black">{{ round($attempt->score) }}</span>
                    <span class="text-xs font-bold text-muted uppercase">PUAN</span>
                </div>
                <span class="text-xs text-muted font-semibold mt-3">Geçme barajı: {{ $exam?->passing_score ?? 70 }} Puan</span>
            </div>

            <!-- Stats Grid -->
            <div class="mt-10 grid grid-cols-2 sm:grid-cols-4 gap-3 text-left">
                <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                    <span class="text-xs font-bold text-muted block">Toplam Soru</span>
                    <strong class="text-2xl font-black text-ink mt-1 block">{{ $attempt->total_questions }}</strong>
                </div>
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 shadow-sm">
                    <span class="text-xs font-bold text-emerald-800 block">Doğru Sayısı</span>
                    <strong class="text-2xl font-black text-emerald-700 mt-1 block">{{ $attempt->correct_count }}</strong>
                </div>
                <div class="rounded-xl bg-red-50 border border-red-100 p-4 shadow-sm">
                    <span class="text-xs font-bold text-red-800 block">Yanlış Sayısı</span>
                    <strong class="text-2xl font-black text-red-700 mt-1 block">{{ $attempt->incorrect_count }}</strong>
                </div>
                <div class="rounded-xl bg-slate-100 border border-slate-200 p-4 shadow-sm">
                    <span class="text-xs font-bold text-slate-700 block">Boş Sayısı</span>
                    <strong class="text-2xl font-black text-slate-700 mt-1 block">{{ $attempt->empty_count }}</strong>
                </div>
            </div>

            @guest
                <div class="mt-6"><x-progress-signup>Sonraki sonuçlarını kaydetmek için üye ol</x-progress-signup></div>
            @endguest

            <!-- Actions -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                @if($attempt->exam && $attempt->exam->slug === 'yanlis-sorular-tekrar-testi')
                    <a href="{{ route('dashboard') }}" class="site-button-primary w-full sm:w-auto py-3 px-6">
                        <x-action-label icon="home">Öğrenci Paneline Dön</x-action-label>
                    </a>
                @elseif($attempt->exam)
                    <form method="POST" action="{{ route('exams.start', $attempt->exam) }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="site-button-primary w-full sm:w-auto py-3 px-6">
                            <x-action-label icon="repeat">Sınavı Tekrar Çöz</x-action-label>
                        </button>
                    </form>
                    <a href="{{ route('exams.index') }}" class="site-button-secondary w-full sm:w-auto py-3 px-6">
                        <x-action-label icon="arrow-left">Tüm Denemelere Dön</x-action-label>
                    </a>
                @elseif($attempt->topicTest)
                    <form method="POST" action="{{ route('topic-tests.start', $attempt->topicTest) }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="site-button-primary w-full sm:w-auto py-3 px-6">
                            <x-action-label icon="repeat">Testi Tekrar Çöz</x-action-label>
                        </button>
                    </form>
                    <a href="{{ $attempt->topicTest->listUrl() }}" class="site-button-secondary w-full sm:w-auto py-3 px-6">
                        <x-action-label icon="arrow-left">Tüm Testlere Dön</x-action-label>
                    </a>
                @endif
            </div>
        </div>

        @auth
        <p x-cloak x-show="bookmarkError" x-text="bookmarkError" role="alert" class="mt-6 text-sm font-semibold text-red-700"></p>
        @endauth
        <!-- Detailed Question by Question Breakdown -->
        <div class="mt-12 space-y-6">
            <h2 class="text-2xl font-extrabold text-ink">Detaylı Soru & Çözüm Analizi</h2>

            @foreach($questions as $index => $q)
                @php
                    $userAnswer = $answers[$q->id] ?? null;
                    $selectedOpt = $userAnswer ? $userAnswer->selected_option : null;
                    $isCorrect = $userAnswer ? $userAnswer->is_correct : false;
                    $isEmpty = empty($selectedOpt);
                @endphp

                <article class="site-card p-6 sm:p-7 border-l-4 {{ $isCorrect ? 'border-l-emerald-500' : ($isEmpty ? 'border-l-slate-400' : 'border-l-red-500') }}">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2"><span class="font-bold text-sm text-slate-800">Soru {{ $index + 1 }}</span><x-question-bookmark :question-expression="(string) $q->id" /></div>
                        @if($isCorrect)
                            <span class="text-xs font-extrabold px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-800">✓ Doğru</span>
                        @elseif($isEmpty)
                            <span class="text-xs font-extrabold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700">○ Boş Bırakıldı</span>
                        @else
                            <span class="text-xs font-extrabold px-2.5 py-1 rounded-md bg-red-100 text-red-800">✕ Yanlış</span>
                        @endif
                    </div>

                    <p class="text-base font-bold text-ink leading-relaxed mb-4">
                        {!! \App\Support\QuestionMedia::clean($q->question_text) !!}
                    </p>

                    <!-- Options -->
                    <div class="space-y-2 mb-4">
                        @foreach($q->options_list as $key => $text)
                            @php
                                $isThisCorrect = ($key === strtoupper($q->correct_option));
                                $isThisSelected = ($key === $selectedOpt);
                            @endphp

                            <div class="flex items-start gap-3 p-3 rounded-xl border text-sm font-medium
                                {{ $isThisCorrect ? 'bg-emerald-50 border-emerald-400 text-emerald-950 font-bold' : ($isThisSelected ? 'bg-red-50 border-red-400 text-red-950' : 'bg-slate-50 border-slate-200 text-slate-700') }}">
                                <span class="grid size-6 shrink-0 place-items-center rounded font-bold text-xs
                                    {{ $isThisCorrect ? 'bg-emerald-600 text-white' : ($isThisSelected ? 'bg-red-600 text-white' : 'bg-slate-200 text-slate-700') }}">
                                    {{ $key }}
                                </span>
                                <span class="flex-1">{{ $text }}</span>
                                @if($isThisCorrect)
                                    <span class="text-xs text-emerald-700 font-extrabold shrink-0">Doğru Cevap</span>
                                @elseif($isThisSelected)
                                    <span class="text-xs text-red-700 font-extrabold shrink-0">Sizin Cevabınız</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Solution / Explanation -->
                    @if($q->explanation)
                        <div class="rounded-xl bg-brand-50/70 border border-brand-200/60 p-4 text-xs sm:text-sm text-brand-950">
                            <strong class="font-extrabold text-brand-900 block mb-1">💡 Çözüm Açıklaması:</strong>
                            <div class="rich-content leading-relaxed text-brand-800">{!! \App\Support\QuestionMedia::clean($q->explanation) !!}</div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>

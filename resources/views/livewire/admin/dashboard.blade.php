<div>
    <x-admin.flash-messages />
    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 mb-7">
        <!-- Members Card -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kullanıcılar</span>
                <span class="p-1.5 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($members) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-1">Son 30 gün: <span class="text-emerald-600 font-bold">+{{ $newMembers30Days }}</span></p>
        </div>

        <!-- Questions Card -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Sorular</span>
                <span class="p-1.5 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($questions) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-1">Aktif soru havuzu</p>
        </div>

        <!-- Exams Card -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Sınavlar</span>
                <span class="p-1.5 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($publishedExams) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-1">Yayındaki deneme</p>
        </div>

        <!-- Attempts Card -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tamamlanan</span>
                <span class="p-1.5 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($finishedAttempts) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-1">Çözülen sınav</p>
        </div>

        <!-- Average Score Card -->
        <div class="col-span-2 lg:col-span-1 bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Ortalama Başarı</span>
                <span class="p-1.5 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900">%{{ $averageScore }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-1">Tüm oturumlar</p>
        </div>
    </div>

    <!-- 14-Day Activity Bar Visualizer -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm mb-7">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">Son 14 Günün Aktivitesi</h2>
                <p class="text-xs text-slate-500 mt-0.5">Günlük yeni kayıt olan üyeler ve çözülen sınav sayıları</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-bold">
                <span class="flex items-center gap-1.5 text-slate-600"><span class="size-2.5 rounded-full bg-blue-600"></span> Sınav Çözümü</span>
                <span class="flex items-center gap-1.5 text-slate-600"><span class="size-2.5 rounded-full bg-emerald-500"></span> Yeni Üye</span>
            </div>
        </div>

        <div class="grid grid-cols-7 sm:grid-cols-14 gap-2 pt-4 border-t border-slate-100">
            @php 
                $maxVal = max(1, collect($dailyActivity)->max(fn($d) => max($d['member_count'], $d['attempt_count'])));
            @endphp
            @foreach($dailyActivity as $day)
                <div class="flex flex-col items-center gap-1.5 group">
                    <div class="h-24 w-full flex items-end justify-center gap-1">
                        <!-- Attempts bar -->
                        <div style="height: {{ max(4, round(($day['attempt_count'] / $maxVal) * 96)) }}px" 
                             class="w-2.5 bg-blue-600 rounded-t-sm group-hover:bg-blue-700 transition" 
                             title="Sınav: {{ $day['attempt_count'] }}"></div>
                        <!-- Users bar -->
                        <div style="height: {{ max(4, round(($day['member_count'] / $maxVal) * 96)) }}px" 
                             class="w-2.5 bg-emerald-500 rounded-t-sm group-hover:bg-emerald-600 transition" 
                             title="Üye: {{ $day['member_count'] }}"></div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 group-hover:text-slate-800 transition">{{ $day['date'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Split Grid: Most Missed Questions & Exam Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-7">
        <!-- Most Missed Questions -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">En Çok Yanlış Yapılan Sorular</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Adayların en çok takıldığı sorular ve hata oranları</p>
                </div>
                <a href="{{ route('admin.questions') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">Tümü &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 overflow-x-auto flex-1">
                @forelse($difficultQuestions as $q)
                    <div class="p-3.5 sm:px-5 flex items-center justify-between gap-4 hover:bg-slate-50/70 transition">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs sm:text-sm font-semibold text-slate-800 line-clamp-1">{{ strip_tags($q->question_text) }}</p>
                            <span class="text-[11px] text-slate-400 font-medium">Toplam {{ $q->total_answers }} denemeden {{ $q->wrong_count }} yanlış</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-red-50 text-red-600 border border-red-100 shrink-0">
                            %{{ $q->wrong_ratio }} Hata
                        </span>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-sm font-medium">Henüz yanlış soru istatistiği oluşmadı.</div>
                @endforelse
            </div>
        </div>

        <!-- Exam Performance -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Deneme Sınavı Performansı</h2>
                    <p class="text-xs text-slate-500 mt-0.5">En çok çözülen denemeler ve başarı oranları</p>
                </div>
                <a href="{{ route('admin.exams') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">Tümü &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 overflow-x-auto flex-1">
                @forelse($examPerformance as $ep)
                    <div class="p-3.5 sm:px-5 flex items-center justify-between gap-4 hover:bg-slate-50/70 transition">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs sm:text-sm font-semibold text-slate-800 truncate">{{ $ep->title }}</p>
                            <span class="text-[11px] text-slate-400 font-medium">{{ $ep->attempts_count }} oturum, {{ $ep->passed_count }} başarılı aday</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold {{ $ep->avg_score >= 70 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                Ort. %{{ round($ep->avg_score ?? 0, 1) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-sm font-medium">Kayıtlı deneme sınavı sonucu bulunamadı.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

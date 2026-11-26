@extends('layouts.app')

@section('title', 'Kaydettiğim Sorular - ' . $siteName)

@section('content')
<x-question-bookmark-scripts />
<div x-data="questionBookmarks({ savedQuestionIds: @js($savedQuestions->pluck('question_id')->values()), toggleSaveUrl: @js(url('/soru')), csrfToken: @js(csrf_token()) })" class="site-page-content">
    <div class="site-container pt-4 sm:pt-5 pb-8 sm:pb-12">
        <!-- Breadcrumbs -->
        <nav aria-label="Sayfa yolu" class="flex flex-wrap items-center gap-2 text-xs text-muted mb-4 sm:mb-5">
            <a href="{{ route('dashboard') }}" class="hover:text-brand-700">Öğrenci Portalı</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page" class="font-bold text-ink">Kaydettiğim Sorular</span>
        </nav>

        <header class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Öğrenci Portalı</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Kaydettiğim Sorular</h1>
                <p class="mt-2 text-sm text-muted">Daha sonra tekrar gözden geçirmek üzere işaretlediğiniz sorular.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="site-button-secondary w-full sm:w-auto">
                <x-action-label icon="arrow-left">Genel Bakış'a Dön</x-action-label>
            </a>
        </header>

        <p x-cloak x-show="bookmarkError" x-text="bookmarkError" role="alert" class="mb-4 text-sm font-semibold text-red-700"></p>
        <div class="space-y-6">
            @forelse($savedQuestions as $item)
                @php $q = $item->question; @endphp
                @if($q)
                    <article x-show="savedQuestionIds.includes({{ $q->id }})" class="site-card p-6 sm:p-7">
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <div class="flex items-center gap-2"><span class="stitch-pill text-xs">Soru #{{ $q->id }}</span><x-question-bookmark :question-expression="(string) $q->id" /></div>
                            <span class="text-xs text-muted">Kaydedilme: {{ $item->created_at->diffForHumans() }}</span>
                        </div>

                        <p class="text-base font-semibold text-ink leading-relaxed">
                            {!! \App\Support\QuestionMedia::clean($q->question_text) !!}
                        </p>


                        <div class="mt-5 grid sm:grid-cols-2 gap-2.5">
                            @foreach($q->options_list as $letter => $val)
                                @if($val)
                                    <div class="p-3 rounded-xl border {{ $letter === $q->correct_option ? 'border-emerald-300 bg-emerald-50/70 text-emerald-950 font-semibold' : 'border-slate-200 bg-slate-50/50 text-slate-700' }} text-sm flex items-start gap-2.5">
                                        <span class="size-6 rounded-lg {{ $letter === $q->correct_option ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }} flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ $letter }}
                                        </span>
                                        <span class="pt-0.5">{{ $val }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @if($q->explanation)
                            <div class="mt-4 p-4 rounded-xl bg-brand-50/70 border border-brand-100 text-xs sm:text-sm text-brand-950">
                                <strong>Açıklama:</strong><div class="rich-content">{!! \App\Support\QuestionMedia::clean($q->explanation) !!}</div>
                            </div>
                        @endif
                    </article>
                @endif
            @empty
            @endforelse

                <div x-cloak x-show="savedQuestionIds.length === 0" class="site-card p-12 text-center text-muted">
                    <svg class="size-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                    </svg>
                    <p class="font-bold text-ink">Henüz kayıtlı soru bulunmuyor.</p>
                    <p class="text-xs text-muted mt-1">Deneme, konu testi veya sonuç ekranındaki yer imi düğmesiyle soruları kaydedebilirsiniz.</p>
                </div>

        </div>
    </div>
</div>
@endsection

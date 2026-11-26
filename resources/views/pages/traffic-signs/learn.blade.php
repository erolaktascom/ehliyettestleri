@extends('layouts.app')
@section('title', $category->name . ' - İşaretleri Öğren - ' . $siteName)
@section('test-session', '1')

@section('content')
<div x-data="signLearner(@js($learnerConfig))" class="sign-learning pb-10">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur shadow-sm" aria-label="Öğrenme araç çubuğu">
        <div class="site-container flex items-center gap-3 py-3 sm:gap-5 sm:py-4">
            <a href="{{ route('traffic-signs.index') }}" aria-label="Kategorilere dön"
               @click="if (busy) { $event.preventDefault(); }" :aria-disabled="busy"
               class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-700" style="min-height:44px">
                <x-student-icon name="arrow-left" /> Çıkış
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm sm:text-lg font-extrabold text-ink" title="{{ $category->name }}">{{ $category->name }}</h1>
                @auth
                <div class="mt-2 flex items-center gap-2">
                    <div role="progressbar" aria-label="Öğrenme ilerlemesi" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progressPercent"
                         :aria-valuetext="knownCount + ' / ' + signs.length + ' işaret öğrenildi'" class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-700 transition-[width] motion-reduce:transition-none" :style="{width: progressPercent + '%'}"></div>
                    </div>
                    <span class="text-xs font-bold text-brand-700 tabular-nums" x-text="'%' + progressPercent"></span>
                </div>
                @endauth
            </div>
            <span class="shrink-0 rounded-xl bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">İşaretler</span>
        </div>
    </header>

    <div class="site-container pt-6">
        <div x-cloak x-show="error" role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <span x-text="error"></span> <button type="button" @click="retry()" :disabled="busy" class="font-bold underline p-2">Yeniden Dene</button>
        </div>
        @if($signs->isEmpty())
            <div class="site-card p-8 text-center"><h2 class="text-xl font-bold">Bu kategoride henüz yayımlanmış işaret yok.</h2><a href="{{ route('traffic-signs.index') }}" class="site-button-primary mt-5">Kategorilere dön</a></div>
        @else
            @auth
            <section x-cloak x-show="completed" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5" role="status">
                <h2 class="font-extrabold text-emerald-800">Bu kategoriyi tamamladın!</h2>
                <p class="mt-1 text-sm text-emerald-800">Tüm işaretleri öğrendim olarak işaretledin. İstersen yeniden gözden geçirebilirsin.</p>
                <div class="mt-3 flex flex-wrap gap-3"><button type="button" @click="moveTo(signs[0].id, 'all')" :disabled="busy" class="site-button-secondary">İşaretleri Gözden Geçir</button><a href="{{ route('traffic-signs.index') }}" class="site-button-primary">Diğer Kategoriler</a></div>
            </section>

            <div class="mb-5 flex flex-wrap items-center gap-2" aria-label="İşaret filtreleri">
                @foreach(['all' => 'Tümü', 'unlearned' => 'Öğrenmediklerim', 'review' => 'Tekrar Edeceklerim'] as $key => $label)
                    <button type="button" @click="setFilter('{{ $key }}')" :disabled="busy" :aria-pressed="filter === '{{ $key }}'"
                            class="sign-filter" :class="{'is-active': filter === '{{ $key }}'}">{{ $label }} <span x-text="{{ $key === 'all' ? 'signs.length' : ($key === 'review' ? 'reviewCount' : 'signs.length - knownCount') }}"></span></button>
                @endforeach
            </div>

            @endauth
            <div class="sign-workspace grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6 items-start">
                <div class="site-card p-5 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-5">
                        <span class="text-sm font-bold text-muted"><span x-text="currentSign ? currentIndex + 1 : 0" class="text-brand-700"></span> / <span x-text="filteredSigns.length"></span> İşaret</span>
                        <button x-ref="mapButton" type="button" @click="mapExpanded = !mapExpanded" :aria-expanded="mapExpanded" aria-controls="learning-sign-map" class="lg:hidden site-button-secondary text-xs" style="min-height:44px"><x-student-icon name="sign" /> İşaret Haritası</button>
                    </div>

                    {{-- One shared map: inline on mobile, in the right column on desktop. --}}
                    <div class="sign-map-anchor">
                        <aside id="learning-sign-map" class="sign-map" :class="{'is-open': mapExpanded}" aria-label="İşaret haritası"
                               @keydown.escape.stop.prevent="mapExpanded = false; $refs.mapButton.focus()">
                            <h2 class="font-extrabold text-sm mb-4">İşaret Haritası</h2>
                            <div class="sign-map-grid">
                                <template x-for="(sign, index) in filteredSigns" :key="sign.id">
                                    <button type="button" @click="selectSign(sign.id)" :disabled="busy" :aria-current="currentId === sign.id ? 'step' : null"
                                            :aria-label="sign.name + ' — ' + statusLabel(sign.id)" :title="sign.name" class="sign-map-item"
                                            :class="{'is-current':currentId === sign.id, 'is-known':statuses[sign.id] === 'known', 'is-review':statuses[sign.id] === 'review'}">
                                        <template x-if="imageFor(sign)"><img :src="imageFor(sign)" alt="" x-on:error="brokenImages[sign.id] = true" loading="lazy"></template>
                                        <span x-show="!imageFor(sign)" class="text-xs font-bold" x-text="index + 1"></span>
                                        <span x-show="imageFor(sign)" class="sign-map-number" x-text="index + 1"></span>
                                    </button>
                                </template>
                            </div>
                            <p x-show="!filteredSigns.length" class="text-xs text-muted">Bu filtrede işaret yok.</p>
                            @auth
                            <div class="mt-4 flex flex-wrap gap-3 text-xs text-muted"><span><i class="sign-key known"></i> Öğrenildi</span><span><i class="sign-key review"></i> Tekrar</span></div>
                            @endauth
                        </aside>
                    </div>

                    <template x-if="currentSign">
                        <article :aria-busy="busy">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="font-mono font-bold text-muted" x-text="currentSign.code || ''"></span>
                                @auth
                                <span class="rounded-lg bg-slate-50 px-3 py-1.5 font-semibold text-muted" x-text="statusLabel(currentSign.id)"></span>
                                @endauth
                            </div>
                            <div class="sign-visual">
                                <template x-if="imageFor(currentSign)"><img :src="imageFor(currentSign)" :alt="currentSign.name" x-on:error="brokenImages[currentSign.id] = true"></template>
                                <div x-show="!imageFor(currentSign)" class="text-center text-slate-400"><svg class="mx-auto mb-3" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 6-6 4 4 3-3 5 5"/></svg><p class="text-sm font-semibold">Görsel eklenmemiş</p></div>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-center text-ink" x-text="currentSign.name"></h2>
                            <p class="mt-3 text-sm sm:text-base text-muted leading-relaxed text-center whitespace-pre-line" x-text="currentSign.meaning"></p>
                            @auth
                            <div class="mt-7 grid grid-cols-2 gap-3 border-t border-slate-100 pt-5">
                                <button type="button" @click="saveReview('review')" :disabled="busy" class="site-button-secondary disabled:opacity-50"><x-action-label icon="repeat">Tekrar Et</x-action-label></button>
                                <button type="button" @click="saveReview('known')" :disabled="busy" class="site-button-primary disabled:opacity-50"><x-action-label icon="check">Öğrendim</x-action-label></button>
                            </div>
                            @endauth
                            <div class="mt-5 flex items-center justify-between gap-3">
                                <button type="button" @click="prev()" :disabled="busy || currentIndex <= 0" class="site-button-secondary disabled:opacity-40"><x-action-label icon="arrow-left">Önceki</x-action-label></button>
                                <button type="button" @click="next()" :disabled="busy || currentIndex >= filteredSigns.length - 1" class="site-button-secondary disabled:opacity-40"><x-action-label icon="arrow">Sonraki</x-action-label></button>
                            </div>
                            <p x-show="currentIndex === filteredSigns.length - 1" class="mt-4 text-xs text-center text-muted">Bu listedeki son işarettesin.</p>
                            @guest
                                <section aria-labelledby="sign-signup-title" class="mt-6 flex flex-col gap-4 rounded-xl border border-brand-100 bg-brand-50 p-4 sm:flex-row sm:items-center">
                                    <div class="flex flex-1 items-start gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-brand-700" aria-hidden="true"><x-student-icon name="bookmark" /></span>
                                        <div>
                                            <h3 id="sign-signup-title" class="text-sm font-bold text-ink">Tekrar listeni oluştur</h3>
                                            <p class="mt-1 text-sm leading-relaxed text-muted">Üye olduğunda zorlandığın işaretleri listene ekleyip yeniden çalışabilirsin.</p>
                                        </div>
                                    </div>
                                    <x-progress-signup class="shrink-0" />
                                </section>
                            @endguest
                        </article>
                    </template>
                    <div x-cloak x-show="!currentSign" class="py-10 text-center">
                        <h2 class="text-lg font-bold" x-text="filter === 'review' ? 'Tekrar edilecek işaretin yok.' : 'Bu listede işaret kalmadı.'"></h2>
                        <button type="button" @click="setFilter('all')" :disabled="busy" class="site-button-primary mt-5">Tüm İşaretleri Gör</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .sign-learning .portal-icon { width:18px;height:18px;flex-shrink:0; }
    .sign-learning button { min-height:44px; }
    .sign-learning button:focus-visible,.sign-learning a:focus-visible { outline:3px solid #4f46e5;outline-offset:3px; }
    .sign-learning .site-button-primary,.sign-learning .site-button-secondary { gap:8px; }
    .sign-filter { padding:10px 14px;border:1px solid #e2e8f0;border-radius:12px;background:white;font-size:12px;font-weight:700;color:#475569; }
    .sign-filter span { margin-left:5px;opacity:.75; }
    .sign-filter.is-active { color:#2436df;border-color:#b9c3ff;background:#eef2ff; }
    .sign-visual { min-height:250px;display:grid;place-items:center;margin:20px 0;background:#f8fafc;border-radius:20px;padding:24px; }
    .sign-visual img { width:230px;height:230px;object-fit:contain; }
    .sign-map { display:none;margin-bottom:24px;padding:16px;border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc; }
    .sign-map.is-open { display:block; }
    .sign-map-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(48px,1fr));gap:8px;max-height:420px;overflow-y:auto;padding:4px; }
    .sign-map-item { position:relative;min-width:0;height:58px;border:1px solid #dce2eb;border-radius:12px;background:white;display:grid;place-items:center; }
    .sign-map-item img { width:36px;height:36px;object-fit:contain; }
    .sign-map-item.is-known { background:#ecfdf5;border-color:#34d399; }
    .sign-map-item.is-review { background:#fffbeb;border-color:#fbbf24; }
    .sign-map-item.is-current { outline:2px solid #3341e7;outline-offset:2px; }
    .sign-map-number { position:absolute;right:3px;bottom:1px;font-size:8px;color:#64748b; }
    .sign-key { display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:3px; }.sign-key.known{background:#34d399}.sign-key.review{background:#fbbf24}
    @media(min-width:1024px) { .sign-workspace { position:relative;min-height:440px; } .sign-map-anchor { position:absolute;left:calc(100% - 320px);top:0;width:320px; } .sign-map { display:block;background:white;padding:20px; } }
    @media(max-width:400px) { .sign-filter {padding:9px 10px;font-size:11px}.sign-visual img{width:190px;height:190px} }
</style>
@endpush
@push('scripts')
<script>@include('partials.traffic-sign-learning')</script>
@endpush
